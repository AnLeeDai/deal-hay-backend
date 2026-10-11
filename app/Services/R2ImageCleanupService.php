<?php

namespace App\Services;

use App\Jobs\DeleteR2Images;

class R2ImageCleanupService
{
    /** @param iterable<array-key, string|null> $paths */
    public function deleteLater(iterable $paths): void
    {
        $paths = collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && filled($path))
            ->map(fn (string $path): string => $path)
            ->unique()
            ->values()
            ->all();

        if ($paths === []) {
            return;
        }

        DeleteR2Images::dispatch($paths)->afterCommit();
    }
}
