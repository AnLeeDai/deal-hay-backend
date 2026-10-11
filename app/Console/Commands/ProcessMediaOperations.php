<?php

namespace App\Console\Commands;

use App\Models\MediaOperation;
use App\Services\ChatAttachmentService;
use App\Services\ImageService;
use App\Services\StagedImageUploadService;
use App\Services\UploadOwner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ProcessMediaOperations extends Command
{
    protected $signature = 'media:process-operations {--limit=100}';

    protected $description = 'Process durable media requests written to the shared database';

    public function handle(ImageService $images, StagedImageUploadService $uploads, ChatAttachmentService $attachments): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if ($limit === false) {
            return self::FAILURE;
        }
        $failed = 0;
        $ids = MediaOperation::query()->where(fn ($query) => $query->whereNull('claimed_at')->orWhere('claimed_at', '<', now()->subMinutes(5)))->oldest()->limit($limit)->pluck('id');
        foreach ($ids as $id) {
            $operation = DB::transaction(function () use ($id): ?MediaOperation {
                $operation = MediaOperation::query()->lockForUpdate()->find($id);
                if ($operation === null || $operation->claimed_at?->isAfter(now()->subMinutes(5))) {
                    return null;
                }
                $operation->update(['claimed_at' => now()]);

                return $operation;
            });
            if ($operation === null) {
                continue;
            }
            try {
                $data = $operation->payload;
                match ($operation->operation) {
                    'delete-many' => $images->deleteMany($data['paths']),
                    'chat/delete' => $attachments->deleteStoredAttachment($data['path'], $data['disk']),
                    'staged/discard' => $uploads->discard(new UploadOwner($data['user_id'], $data['user_code']), $data['tokens']),
                    default => throw new RuntimeException('Unknown media operation.'),
                };
                $operation->delete();
            } catch (Throwable $exception) {
                report($exception);
                $operation->update(['claimed_at' => null]);
                $failed++;
            }
        }
        $this->info('Media operations processed; failures: '.$failed);

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
