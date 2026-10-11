<?php

namespace App\Services;

use App\Enums\ImagePurposeEnum;
use App\Jobs\DeleteChatAttachment;
use App\Models\ChatMessage;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Throwable;

class ChatAttachmentService
{
    public function __construct(private readonly ImageService $images) {}

    /**
     * @return array{
     *     disk: string,
     *     path: string|false|null,
     *     is_image: bool,
     *     is_video: bool,
     *     name: string|null,
     *     mime: string|null,
     *     size: int|null
     * }
     */
    public function store(?UploadedFile $attachment, string $conversationId): array
    {
        if ($attachment !== null) {
            $mime = (string) $attachment->getMimeType();
            Validator::make(['attachment' => $attachment], ['attachment' => [
                'file', 'mimetypes:'.implode(',', [...ImagePurposeEnum::Chat->inputMimeTypes(),
                    'video/mp4', 'video/webm', 'video/quicktime', 'application/pdf', 'text/plain', 'text/csv',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/zip', 'application/x-zip-compressed', 'application/vnd.rar', 'application/x-rar-compressed']),
                'max:'.(str_starts_with($mime, 'image/') ? ImagePurposeEnum::Chat->maxKilobytes() : 3072),
            ]])->validate();
        }
        $originalMime = $attachment?->getMimeType();
        $isImage = Str::startsWith((string) $originalMime, 'image/');
        $isVideo = Str::startsWith((string) $originalMime, 'video/');
        $disk = (string) config('filesystems.chat_media_disk');
        $path = match (true) {
            $attachment === null => null,
            $isImage => $this->images->upload(
                $attachment,
                'chat-media/'.$conversationId,
                'attachment',
                $disk,
            ),
            default => $attachment->store('chat-media/'.$conversationId, $disk),
        };
        $size = null;

        try {
            $size = $isImage && $path
                ? $this->images->size($path, $disk)
                : $attachment?->getSize();
        } catch (Throwable $exception) {
            $this->cleanup($path, $disk, $isImage);

            throw $exception;
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'is_image' => $isImage,
            'is_video' => $isVideo,
            'name' => $isImage
                ? Str::beforeLast((string) $attachment?->getClientOriginalName(), '.').'.webp'
                : $attachment?->getClientOriginalName(),
            'mime' => $isImage ? 'image/webp' : $originalMime,
            'size' => $size,
        ];
    }

    public function deleteStoredAttachment(string $path, string $disk): void
    {
        if (! Storage::disk($disk)->delete($path)) {
            throw new RuntimeException('Cannot delete attachment.');
        }
    }

    public function cleanup(string|false|null $path, string $disk, bool $isImage): void
    {
        if (! $path) {
            return;
        }

        try {
            if ($isImage) {
                if (! $this->images->delete($path, $disk)) {
                    throw new RuntimeException('Cannot delete attachment.');
                }
            } else {
                $this->deleteStoredAttachment($path, $disk);
            }
        } catch (Throwable $exception) {
            report($exception);
            DeleteChatAttachment::dispatch($path, $disk)
                ->onConnection('database_outbox')
                ->afterCommit();
        }
    }

    /** @return array{callback: Closure(): void, headers: array<string, string>} */
    public function downloadById(string $id): array
    {
        return $this->download(ChatMessage::query()->findOrFail($id));
    }

    public function download(ChatMessage $message): array
    {
        abort_unless($message->attachment_disk && $message->attachment_path, 404);

        $chatMediaDisk = (string) config('filesystems.chat_media_disk');
        abort_unless(
            in_array($message->attachment_disk, [ImageService::DISK, $chatMediaDisk], true),
            404,
        );

        if ($this->usesImageService($message)) {
            abort_unless(
                $this->images->exists($message->attachment_path, $message->attachment_disk),
                404,
            );
        } else {
            abort_unless(
                Storage::disk($message->attachment_disk)->exists($message->attachment_path),
                404,
            );
        }

        return [
            'callback' => function () use ($message): void {
                $stream = $this->readStream($message);
                fpassthru($stream);
                fclose($stream);
            },
            'headers' => $this->headers($message),
        ];
    }

    /** @return resource */
    private function readStream(ChatMessage $message)
    {
        if ($this->usesImageService($message)) {
            return $this->images->readStream(
                (string) $message->attachment_path,
                (string) $message->attachment_disk,
            );
        }

        $stream = Storage::disk((string) $message->attachment_disk)
            ->readStream((string) $message->attachment_path);

        abort_if($stream === false, 404);

        return $stream;
    }

    private function usesImageService(ChatMessage $message): bool
    {
        return $message->attachment_disk === ImageService::DISK
            || Str::startsWith((string) $message->attachment_mime, 'image/');
    }

    /** @return array<string, string> */
    private function headers(ChatMessage $message): array
    {
        $disposition = Str::startsWith(
            (string) $message->attachment_mime,
            ['image/', 'video/', 'application/pdf'],
        )
            ? HeaderUtils::DISPOSITION_INLINE
            : HeaderUtils::DISPOSITION_ATTACHMENT;

        return [
            'Content-Type' => (string) $message->attachment_mime,
            'Content-Disposition' => HeaderUtils::makeDisposition(
                $disposition,
                (string) $message->attachment_name,
                'attachment',
            ),
            'Content-Length' => (string) $message->attachment_size,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
