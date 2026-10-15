<?php

namespace App\Actions;

use App\Events\ChatMessageSentEvent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatAttachmentService;
use App\Services\ChatMessageIdempotencyService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreChatMessage
{
    public function __construct(
        private readonly ChatAttachmentService $attachments,
        private readonly ChatMessageIdempotencyService $idempotency,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{message: ChatMessage, replayed: bool}
     */
    public function handle(
        User $sender,
        ChatConversation $conversation,
        array $data,
        ?UploadedFile $attachment,
    ): array {
        $clientMessageId = (string) $data['client_message_id'];
        $requestFingerprint = $this->idempotency->fingerprint([
            'body' => (string) ($data['body'] ?? ''),
            'attachment_sha256' => $this->attachmentHash($attachment),
            ...filled($data['reply_to_id'] ?? null) ? ['reply_to_id' => $data['reply_to_id']] : [],
        ]);
        $existingMessage = $this->idempotency->find(
            $conversation,
            (string) $sender->getKey(),
            $clientMessageId,
        );

        if ($existingMessage !== null) {
            $this->idempotency->assertMatches($existingMessage, $requestFingerprint);

            return ['message' => $existingMessage, 'replayed' => true];
        }

        $storedAttachment = $this->attachments->store($attachment, (string) $conversation->getKey());

        abort_if(
            $attachment !== null && $storedAttachment['path'] === false,
            500,
            'Không thể lưu tệp đính kèm.',
        );

        try {
            $message = DB::transaction(function () use ($attachment, $clientMessageId, $conversation, $data, $requestFingerprint, $sender, $storedAttachment): ChatMessage {
                $message = $conversation->messages()->create([
                    'sender_id' => $sender->getKey(),
                    'client_message_id' => $clientMessageId,
                    'request_fingerprint' => $requestFingerprint,
                    'reply_to_id' => $data['reply_to_id'] ?? null,
                    'body' => (string) (($data['body'] ?? null) ?: match (true) {
                        $storedAttachment['is_image'] => '[Hình ảnh]',
                        $storedAttachment['is_video'] => '[Video]',
                        default => '[Tệp đính kèm]',
                    }),
                    'attachment_disk' => $attachment ? $storedAttachment['disk'] : null,
                    'attachment_path' => $storedAttachment['path'] ?: null,
                    'attachment_name' => $storedAttachment['name'],
                    'attachment_mime' => $storedAttachment['mime'],
                    'attachment_size' => $storedAttachment['size'],
                ]);
                $conversation->touch();
                $this->idempotency->loadForDelivery($message);
                broadcast(new ChatMessageSentEvent($message))->toOthers();

                return $message;
            }, attempts: 5);
        } catch (UniqueConstraintViolationException $exception) {
            $this->cleanup($storedAttachment);
            $existingMessage = $this->idempotency->find(
                $conversation,
                (string) $sender->getKey(),
                $clientMessageId,
            );

            if ($existingMessage === null) {
                throw $exception;
            }

            $this->idempotency->assertMatches($existingMessage, $requestFingerprint);

            return ['message' => $existingMessage, 'replayed' => true];
        } catch (Throwable $exception) {
            $this->cleanup($storedAttachment);

            throw $exception;
        }

        return ['message' => $message, 'replayed' => false];
    }

    private function attachmentHash(?UploadedFile $attachment): string|false|null
    {
        return $attachment?->getRealPath()
            ? hash_file('sha256', $attachment->getRealPath())
            : null;
    }

    /**
     * @param array{
     *     disk: string,
     *     path: string|false|null,
     *     is_image: bool,
     *     is_video: bool,
     *     name: string|null,
     *     mime: string|null,
     *     size: int|null
     * } $storedAttachment
     */
    private function cleanup(array $storedAttachment): void
    {
        $this->attachments->cleanup(
            $storedAttachment['path'],
            $storedAttachment['disk'],
            $storedAttachment['is_image'],
        );
    }
}
