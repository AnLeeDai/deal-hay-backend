<?php

use App\Jobs\DeleteChatAttachment;
use App\Models\StagedImageUpload;
use App\Models\User;
use App\Services\ChatAttachmentService;
use App\Services\ImageService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;

it('queues failed image cleanup on the attachment disk for retry', function (): void {
    Queue::fake([DeleteChatAttachment::class]);
    Exceptions::fake();
    $path = 'chat-media/conversation/image.webp';
    $images = Mockery::mock(ImageService::class);
    $images->shouldReceive('delete')->once()->with($path, 'r2_private')->andReturn(false);
    $attachments = new ChatAttachmentService($images);

    $attachments->cleanup($path, 'r2_private', true);

    Queue::assertPushedOn('images', DeleteChatAttachment::class, fn (DeleteChatAttachment $job): bool => $job->path === $path
        && $job->disk === 'r2_private'
        && $job->connection === 'database_outbox');
    Exceptions::assertReported(RuntimeException::class);
});

it('queues failed file cleanup when private storage is unavailable', function (): void {
    Queue::fake([DeleteChatAttachment::class]);
    Exceptions::fake();
    $path = 'chat-media/conversation/note.txt';
    $failure = new RuntimeException('Private storage unavailable.');
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('delete')->once()->with($path)->andThrow($failure);
    Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);

    app(ChatAttachmentService::class)->cleanup($path, 'r2_private', false);

    Queue::assertPushedOn('images', DeleteChatAttachment::class, fn (DeleteChatAttachment $job): bool => $job->path === $path && $job->disk === 'r2_private');
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception === $failure);
});

it('deletes the retried attachment only from its recorded disk', function (): void {
    $privateDisk = Storage::fake('r2_private');
    $publicDisk = Storage::fake('r2');
    $path = 'chat-media/conversation/note.txt';
    $privateDisk->put($path, 'Private attachment.');
    $publicDisk->put($path, 'Another object.');

    (new DeleteChatAttachment($path, 'r2_private'))->handle(app(ChatAttachmentService::class));

    $privateDisk->assertMissing($path);
    $publicDisk->assertExists($path);
});

it('fails the attachment cleanup job when storage refuses deletion', function (): void {
    $path = 'chat-media/conversation/note.txt';
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('delete')->once()->with($path)->andReturn(false);
    Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
    $job = new DeleteChatAttachment($path, 'r2_private');

    expect(fn () => $job->handle(app(ChatAttachmentService::class)))
        ->toThrow(RuntimeException::class, 'Cannot delete attachment.');
});

it('does not queue cleanup when there was no stored attachment', function (): void {
    Queue::fake();

    app(ChatAttachmentService::class)->cleanup(null, 'r2_private', false);

    Queue::assertNothingPushed();
});

it('reports staged object pruning as failed when R2 refuses deletion', function (): void {
    $this->freezeTime();
    Exceptions::fake();
    $path = 'staged-images/owner/abandoned.source';
    $driver = Mockery::mock(FilesystemOperator::class);
    $driver->shouldReceive('listContents')->once()->with('staged-images', true)
        ->andReturn(new DirectoryListing([new FileAttributes($path, lastModified: now()->subHours(2)->getTimestamp())]));
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('getDriver')->once()->andReturn($driver);
    $disk->shouldReceive('delete')->once()->with($path)->andReturn(false);
    $filesystems = Mockery::mock(FilesystemManager::class);
    $filesystems->shouldReceive('disk')->once()->with('r2')->andReturn($disk);
    app()->instance(FilesystemManager::class, $filesystems);

    $this->artisan('app:prune-staged-images')
        ->expectsOutputToContain('matched 1 file(s), deleted 0, failed 1.')
        ->assertFailed();

    Exceptions::assertReported(RuntimeException::class);
});

it('removes abandoned prepared images when expired upload records are pruned', function (): void {
    $this->freezeTime();
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    $path = $user->user_code.'/products/abandoned.webp';
    $upload = StagedImageUpload::query()->create([
        'user_id' => $user->getKey(),
        'path' => 'staged-images/'.$user->user_code.'/abandoned.source',
        'expected_size' => 100,
        'expires_at' => now()->subHours(2),
        'prepared_at' => now()->subHours(3),
        'destination_path' => $path,
    ]);
    $disk->put($path, 'Abandoned image.');

    $this->artisan('app:prune-staged-upload-records')
        ->expectsOutputToContain('matched 1, deleted 1, failed 0.')
        ->assertSuccessful();

    $this->assertModelMissing($upload);
    $disk->assertMissing($path);
});

it('preserves consumed product images when expired upload records are pruned', function (): void {
    $this->freezeTime();
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    $path = $user->user_code.'/products/consumed.webp';
    $upload = StagedImageUpload::query()->create([
        'user_id' => $user->getKey(),
        'path' => 'staged-images/'.$user->user_code.'/consumed.source',
        'expected_size' => 100,
        'expires_at' => now()->subHours(2),
        'prepared_at' => now()->subHours(3),
        'consumed_at' => now()->subHours(3),
        'destination_path' => $path,
    ]);
    $disk->put($path, 'Product image.');

    $this->artisan('app:prune-staged-upload-records')->assertSuccessful();

    $this->assertModelMissing($upload);
    $disk->assertExists($path);
});

it('honors the pruning limit across deletion-safe pages and dry runs', function (bool $dryRun): void {
    $this->freezeTime();
    $user = User::factory()->customer()->create();
    $rows = [];

    foreach (range(1, 207) as $index) {
        $rows[] = [
            'id' => '00000000-0000-4000-8000-'.str_pad((string) $index, 12, '0', STR_PAD_LEFT),
            'user_id' => $user->getKey(),
            'path' => 'staged-images/'.$user->user_code.'/batch-'.$index.'.source',
            'expected_size' => 100,
            'expires_at' => now()->subHours(2)->subSeconds($index),
            'consumed_at' => now()->subHours(3),
        ];
    }

    DB::table('staged_image_uploads')->insert($rows);
    $images = Mockery::mock(ImageService::class);
    $images->shouldNotReceive('delete');
    app()->instance(ImageService::class, $images);

    $this->artisan('app:prune-staged-upload-records', ['--limit' => 205, '--dry-run' => $dryRun])
        ->expectsOutputToContain('matched 205, deleted '.($dryRun ? 0 : 205).', failed 0.')
        ->assertSuccessful();

    $this->assertDatabaseCount('staged_image_uploads', $dryRun ? 207 : 2);
    $this->assertDatabaseHas('staged_image_uploads', ['id' => $rows[205]['id']]);
    $this->assertDatabaseHas('staged_image_uploads', ['id' => $rows[206]['id']]);
})->with([true, false]);
