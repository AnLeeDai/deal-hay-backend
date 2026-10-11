<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemManager;
use Throwable;

#[Signature('app:prune-staged-images {--older-than=60 : Minimum age in minutes} {--limit=1000 : Maximum files to remove} {--dry-run : Only report matching files}')]
#[Description('Remove abandoned direct-upload images from the R2 staging prefix')]
class PruneStagedImages extends Command
{
    private const string STAGED_PREFIX = 'staged-images';

    public function __construct(private readonly FilesystemManager $filesystems)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $olderThanMinutes = $this->positiveIntegerOption('older-than', 5, 10_080);
        $limit = $this->positiveIntegerOption('limit', 1, 10_000);

        if ($olderThanMinutes === null || $limit === null) {
            return self::FAILURE;
        }

        $disk = $this->filesystems->disk('r2');
        $threshold = now()->subMinutes($olderThanMinutes)->timestamp;
        $matched = 0;
        $deleted = 0;
        $failed = 0;

        try {
            foreach ($disk->getDriver()->listContents(self::STAGED_PREFIX, true) as $attributes) {
                if (! $attributes->isFile() || $attributes->lastModified() === null) {
                    continue;
                }

                if ($attributes->lastModified() > $threshold) {
                    continue;
                }

                $matched++;

                if (! $this->option('dry-run')) {
                    try {
                        $disk->delete($attributes->path());
                        $deleted++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }

                if ($matched >= $limit) {
                    break;
                }
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Unable to list the staged image prefix.');

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Staged image cleanup matched %d file(s), deleted %d, failed %d.',
            $matched,
            $deleted,
            $failed,
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function positiveIntegerOption(string $name, int $minimum, int $maximum): ?int
    {
        $value = filter_var($this->option($name), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $minimum, 'max_range' => $maximum],
        ]);

        if ($value === false) {
            $this->components->error(sprintf(
                'The --%s option must be between %d and %d.',
                $name,
                $minimum,
                $maximum,
            ));

            return null;
        }

        return $value;
    }
}
