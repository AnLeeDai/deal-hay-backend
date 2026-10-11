<?php

namespace App\Jobs;

use App\Services\ChatAttachmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class DeleteChatAttachment implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(
        public readonly string $path,
        public readonly string $disk,
    ) {
        $this->onQueue('images');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [1, 5, 15, 30];
    }

    public function handle(ChatAttachmentService $attachments): void
    {
        $attachments->deleteStoredAttachment($this->path, $this->disk);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            report($exception);
        }
    }
}
