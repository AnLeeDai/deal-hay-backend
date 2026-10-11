<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use Illuminate\Cache\Repository;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

#[Signature('app:migrate-chat-attachments-to-private {--limit=100 : Maximum database records to process} {--dry-run : Report candidates without copying, updating, or deleting}')]
#[Description('Move legacy chat attachments from public R2 to the configured private chat-media disk')]
class MigrateLegacyChatAttachments extends Command
{
    private const string PUBLIC_DISK = 'r2';

    private const string PRIVATE_DISK = 'r2_private';

    private const int LOCK_SECONDS = 3_600;

    public function __construct(
        private readonly FilesystemManager $filesystems,
        private readonly CacheFactory $cache,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = $this->positiveIntegerOption('limit', 1, 1_000);

        if ($limit === null) {
            return self::FAILURE;
        }

        $privateDiskName = (string) config('filesystems.chat_media_disk');

        if ($privateDiskName !== self::PRIVATE_DISK) {
            $this->components->error('The configured chat media disk must be r2_private.');

            return self::FAILURE;
        }

        $privateDiskConfig = (array) config('filesystems.disks.'.$privateDiskName, []);
        $publicBucket = config('filesystems.disks.'.self::PUBLIC_DISK.'.bucket');
        $privateBucket = $privateDiskConfig['bucket'] ?? null;

        if (($privateDiskConfig['visibility'] ?? null) !== 'private'
            || ! filled($publicBucket)
            || ! filled($privateBucket)
            || $publicBucket === $privateBucket) {
            $this->components->error('The public and private R2 disks must use distinct, non-empty buckets.');

            return self::FAILURE;
        }

        try {
            $lock = $this->migrationLock();

            if (! $lock->get()) {
                $this->components->error('Another legacy chat attachment migration is already running.');

                return self::FAILURE;
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Unable to acquire the shared migration lock.');

            return self::FAILURE;
        }

        try {
            return $this->migrateAttachments($privateDiskName, $limit);
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Legacy chat attachment migration could not start or complete its batch.');

            return self::FAILURE;
        } finally {
            try {
                $lock->release();
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function migrateAttachments(string $privateDiskName, int $limit): int
    {
        $candidates = ChatMessage::query()
            ->select([
                'id',
                'attachment_disk',
                'attachment_path',
                'attachment_mime',
                'attachment_size',
                'attachment_public_cleanup_pending',
            ])
            ->whereNotNull('attachment_path')
            ->where(function (Builder $query) use ($privateDiskName): void {
                $query
                    ->where('attachment_disk', self::PUBLIC_DISK)
                    ->orWhere(function (Builder $query) use ($privateDiskName): void {
                        $query
                            ->where('attachment_disk', $privateDiskName)
                            ->where('attachment_public_cleanup_pending', true);
                    });
            })
            ->oldest('id')
            ->limit($limit)
            ->get();

        if ((bool) $this->option('dry-run')) {
            $this->components->info(sprintf(
                'Legacy attachment migration matched %d record(s); dry-run made no changes.',
                $candidates->count(),
            ));

            return self::SUCCESS;
        }

        $publicDisk = $this->filesystems->disk(self::PUBLIC_DISK);
        $privateDisk = $this->filesystems->disk($privateDiskName);
        $copied = 0;
        $migrated = 0;
        $cleaned = 0;
        $deferred = 0;
        $failed = 0;

        foreach ($candidates as $message) {
            try {
                $result = $this->migrateAttachment(
                    $message,
                    $publicDisk,
                    $privateDisk,
                    $privateDiskName,
                );
                $copied += $result['copied'];
                $migrated += $result['migrated'];
                $cleaned += $result['cleaned'];
                $deferred += $result['deferred'];
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
                $this->components->warn(sprintf(
                    'Attachment %s failed and remains retryable; see application logs.',
                    $message->getKey(),
                ));
            }
        }

        $this->components->info(sprintf(
            'Legacy attachment migration matched %d record(s), copied %d, migrated %d, cleaned %d, deferred %d, failed %d.',
            $candidates->count(),
            $copied,
            $migrated,
            $cleaned,
            $deferred,
            $failed,
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return array{copied: int, migrated: int, cleaned: int, deferred: int} */
    private function migrateAttachment(
        ChatMessage $message,
        FilesystemAdapter $publicDisk,
        FilesystemAdapter $privateDisk,
        string $privateDiskName,
    ): array {
        $path = $this->validatedAttachmentPath($message);
        $copied = 0;
        $migrated = 0;

        if ($message->attachment_disk === self::PUBLIC_DISK) {
            $copyResult = $this->ensurePrivateCopy($message, $path, $publicDisk, $privateDisk);
            $copied = $copyResult['copied'];
            $updated = ChatMessage::query()
                ->whereKey($message->getKey())
                ->where('attachment_disk', self::PUBLIC_DISK)
                ->where('attachment_path', $path)
                ->update([
                    'attachment_disk' => $privateDiskName,
                    'attachment_size' => $copyResult['size'],
                    'attachment_public_cleanup_pending' => true,
                ]);

            if ($updated === 0 && ! $this->hasExpectedPrivateState($message, $path, $privateDiskName)) {
                return compact('copied', 'migrated') + ['cleaned' => 0, 'deferred' => 1];
            }

            $migrated = $updated;
        } elseif ($message->attachment_disk === $privateDiskName
            && (bool) $message->attachment_public_cleanup_pending) {
            if ($this->storageExists($publicDisk, $path)) {
                $copyResult = $this->ensurePrivateCopy($message, $path, $publicDisk, $privateDisk);
                $copied = $copyResult['copied'];
            } elseif (! $this->storageExists($privateDisk, $path)) {
                throw new RuntimeException('Both the public source and verified private copy are missing.');
            }
        } else {
            return compact('copied', 'migrated') + ['cleaned' => 0, 'deferred' => 1];
        }

        if (ChatMessage::query()
            ->where('attachment_disk', self::PUBLIC_DISK)
            ->where('attachment_path', $path)
            ->exists()) {
            $this->clearCleanupPending($message, $path, $privateDiskName);

            return compact('copied', 'migrated') + ['cleaned' => 0, 'deferred' => 1];
        }

        $this->deleteVerifiedPublicSource($path, $publicDisk, $privateDisk);
        $this->clearCleanupPending($message, $path, $privateDiskName);

        return compact('copied', 'migrated') + ['cleaned' => 1, 'deferred' => 0];
    }

    /** @return array{copied: int, size: int} */
    private function ensurePrivateCopy(
        ChatMessage $message,
        string $path,
        FilesystemAdapter $publicDisk,
        FilesystemAdapter $privateDisk,
    ): array {
        if (! $this->storageExists($publicDisk, $path)) {
            throw new RuntimeException('The public source object is missing.');
        }

        [$temporaryFile, $sourceHash, $sourceSize] = $this->downloadPublicObject($publicDisk, $path);
        $destinationExisted = $this->storageExists($privateDisk, $path);

        try {
            if (! $destinationExisted) {
                $options = ['visibility' => 'private'];

                if (filled($message->attachment_mime)) {
                    $options['ContentType'] = (string) $message->attachment_mime;
                }

                retry([100, 500], function () use ($options, $path, $privateDisk, $temporaryFile): void {
                    rewind($temporaryFile);

                    if (! $privateDisk->writeStream($path, $temporaryFile, $options)) {
                        throw new RuntimeException('The private disk rejected the attachment stream.');
                    }
                });
            }

            $this->assertObjectMatches($privateDisk, $path, $sourceHash, $sourceSize);
        } catch (Throwable $exception) {
            if (! $destinationExisted
                && ! ChatMessage::query()
                    ->where('attachment_disk', '!=', self::PUBLIC_DISK)
                    ->where('attachment_path', $path)
                    ->exists()) {
                try {
                    $privateDisk->delete($path);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        } finally {
            fclose($temporaryFile);
        }

        return ['copied' => $destinationExisted ? 0 : 1, 'size' => $sourceSize];
    }

    private function deleteVerifiedPublicSource(
        string $path,
        FilesystemAdapter $publicDisk,
        FilesystemAdapter $privateDisk,
    ): void {
        if (! $this->storageExists($publicDisk, $path)) {
            return;
        }

        [$temporaryFile, $sourceHash, $sourceSize] = $this->downloadPublicObject($publicDisk, $path);

        try {
            $this->assertObjectMatches($privateDisk, $path, $sourceHash, $sourceSize);
        } finally {
            fclose($temporaryFile);
        }

        retry([100, 500], function () use ($path, $publicDisk): void {
            if (! $publicDisk->exists($path)) {
                return;
            }

            if (! $publicDisk->delete($path) && $publicDisk->exists($path)) {
                throw new RuntimeException('The public disk rejected source deletion.');
            }

            if ($publicDisk->exists($path)) {
                throw new RuntimeException('The public source still exists after deletion.');
            }
        });
    }

    /** @return array{resource, string, int} */
    private function downloadPublicObject(FilesystemAdapter $publicDisk, string $path): array
    {
        return retry([100, 500], function () use ($path, $publicDisk): array {
            $sourceStream = $publicDisk->readStream($path);

            if (! is_resource($sourceStream)) {
                throw new RuntimeException('Unable to open the public source stream.');
            }

            $temporaryFile = tmpfile();

            if (! is_resource($temporaryFile)) {
                fclose($sourceStream);

                throw new RuntimeException('Unable to create a temporary attachment file.');
            }

            try {
                $bytesCopied = stream_copy_to_stream($sourceStream, $temporaryFile);

                if (! is_int($bytesCopied) || $bytesCopied < 1) {
                    throw new RuntimeException('Unable to read the complete public source object.');
                }

                rewind($temporaryFile);
                $hashContext = hash_init('sha256');
                $bytesHashed = hash_update_stream($hashContext, $temporaryFile);

                if ($bytesHashed !== $bytesCopied) {
                    throw new RuntimeException('Unable to hash the complete public source object.');
                }

                rewind($temporaryFile);

                return [$temporaryFile, hash_final($hashContext), $bytesCopied];
            } catch (Throwable $exception) {
                fclose($temporaryFile);

                throw $exception;
            } finally {
                fclose($sourceStream);
            }
        });
    }

    private function assertObjectMatches(
        FilesystemAdapter $disk,
        string $path,
        string $expectedHash,
        int $expectedSize,
    ): void {
        retry([100, 500], function () use ($disk, $expectedHash, $expectedSize, $path): void {
            if (! $disk->exists($path) || (int) $disk->size($path) !== $expectedSize) {
                throw new RuntimeException('The private copy size does not match the public source.');
            }

            $stream = $disk->readStream($path);

            if (! is_resource($stream)) {
                throw new RuntimeException('Unable to open the private attachment stream.');
            }

            try {
                $hashContext = hash_init('sha256');
                $bytesHashed = hash_update_stream($hashContext, $stream);

                if ($bytesHashed !== $expectedSize
                    || ! hash_equals($expectedHash, hash_final($hashContext))) {
                    throw new RuntimeException('The private copy checksum does not match the public source.');
                }
            } finally {
                fclose($stream);
            }
        });
    }

    private function clearCleanupPending(ChatMessage $message, string $path, string $privateDiskName): void
    {
        ChatMessage::query()
            ->whereKey($message->getKey())
            ->where('attachment_disk', $privateDiskName)
            ->where('attachment_path', $path)
            ->update(['attachment_public_cleanup_pending' => false]);
    }

    private function hasExpectedPrivateState(
        ChatMessage $message,
        string $path,
        string $privateDiskName,
    ): bool {
        return ChatMessage::query()
            ->whereKey($message->getKey())
            ->where('attachment_disk', $privateDiskName)
            ->where('attachment_path', $path)
            ->exists();
    }

    private function validatedAttachmentPath(ChatMessage $message): string
    {
        $path = trim((string) $message->attachment_path);

        if ($path === ''
            || ! Str::startsWith($path, 'chat-media/')
            || Str::contains($path, ['..', '\\'])) {
            throw new RuntimeException('The legacy attachment path is invalid.');
        }

        return $path;
    }

    private function storageExists(FilesystemAdapter $disk, string $path): bool
    {
        return retry([100, 500], fn (): bool => $disk->exists($path));
    }

    private function migrationLock(): Lock
    {
        /** @var Repository $repository */
        $repository = $this->cache->store();
        $store = $repository->getStore();

        if (! $store instanceof LockProvider) {
            throw new RuntimeException('The configured cache store does not support distributed locks.');
        }

        return $store->lock('dealhay:migrate-chat-attachments-to-private', self::LOCK_SECONDS);
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
