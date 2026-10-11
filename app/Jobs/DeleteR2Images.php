<?php

namespace App\Jobs;

use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DeleteR2Images implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    /** @param list<string> $paths */
    public function __construct(public readonly array $paths)
    {
        $this->onQueue('images');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [1, 5, 15, 30];
    }

    public function handle(ImageService $images): void
    {
        $images->deleteMany($this->paths);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            report($exception);
        }
    }
}
