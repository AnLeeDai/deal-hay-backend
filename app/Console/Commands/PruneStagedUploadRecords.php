<?php

namespace App\Console\Commands;

use App\Models\StagedImageUpload;
use App\Services\ImageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

#[Signature('app:prune-staged-upload-records {--limit=10000 : Maximum expired records to remove} {--dry-run : Only report matching records}')]
#[Description('Remove expired one-time direct-upload records after their replay window has closed')]
class PruneStagedUploadRecords extends Command
{
    public function __construct(private readonly ImageService $images)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 50_000],
        ]);

        if ($limit === false) {
            $this->components->error('The --limit option must be between 1 and 50000.');

            return self::FAILURE;
        }

        $threshold = now()->subHour();
        $uploads = StagedImageUpload::query()
            ->select(['id', 'consumed_at', 'destination_path'])
            ->where('expires_at', '<', $threshold)
            ->where(function (Builder $query) use ($threshold): void {
                $query->whereNull('claimed_at')->orWhere('claimed_at', '<', $threshold);
            })
            ->limit($limit)
            ->lazyById(200);

        $matched = 0;
        $deleted = 0;
        $failed = 0;
        foreach ($uploads as $upload) {
            $matched++;

            if ($this->option('dry-run')) {
                continue;
            }

            try {
                if ($upload->consumed_at === null
                    && filled($upload->destination_path)
                    && ! $this->images->delete($upload->destination_path)) {
                    $failed++;

                    continue;
                }

                $upload->delete();
                $deleted++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        $this->components->info(sprintf(
            'Expired staged upload records matched %d, deleted %d, failed %d.',
            $matched,
            $deleted,
            $failed,
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
