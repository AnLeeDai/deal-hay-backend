<?php

use Aws\MockHandler;
use Aws\Result;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Storage;

it('writes canonical R2 object keys to the shared bucket regardless of obsolete private bucket settings', function (string $diskName, string $objectKey, ?string $obsoletePrivateBucket): void {
    $environment = Env::getRepository();
    $previousDriver = $environment->get('R2_DRIVER');
    $previousPrivateDriver = $environment->get('R2_PRIVATE_DRIVER');
    $previousBucket = $environment->get('R2_BUCKET');
    $previousPrivateBucket = $environment->get('R2_PRIVATE_BUCKET');

    try {
        $environment->set('R2_DRIVER', 's3');
        $environment->clear('R2_PRIVATE_DRIVER');
        $environment->set('R2_BUCKET', 'shared-test');
        $obsoletePrivateBucket === null
            ? $environment->clear('R2_PRIVATE_BUCKET')
            : $environment->set('R2_PRIVATE_BUCKET', $obsoletePrivateBucket);
        $filesystems = require config_path('filesystems.php');
        config([
            'filesystems.disks.'.$diskName => array_replace($filesystems['disks'][$diskName], [
                'key' => 'test-key',
                'secret' => 'test-secret',
                'region' => 'auto',
                'endpoint' => 'https://r2.example.test',
            ]),
        ]);
        Storage::forgetDisk($diskName);
        $disk = Storage::disk($diskName);
        $handler = new MockHandler([new Result]);
        $disk->getClient()->getHandlerList()->setHandler($handler);

        $written = $disk->put($objectKey, 'image-bytes', ['ContentType' => 'image/webp']);

        expect($written)->toBeTrue();
        expect($handler->getLastCommand()['Key'])->toBe($objectKey);
        expect($handler->getLastRequest()->getUri()->getPath())->toBe('/shared-test/'.$objectKey);
    } finally {
        $previousDriver === null
            ? $environment->clear('R2_DRIVER')
            : $environment->set('R2_DRIVER', $previousDriver);
        $previousPrivateDriver === null
            ? $environment->clear('R2_PRIVATE_DRIVER')
            : $environment->set('R2_PRIVATE_DRIVER', $previousPrivateDriver);
        $previousBucket === null
            ? $environment->clear('R2_BUCKET')
            : $environment->set('R2_BUCKET', $previousBucket);
        $previousPrivateBucket === null
            ? $environment->clear('R2_PRIVATE_BUCKET')
            : $environment->set('R2_PRIVATE_BUCKET', $previousPrivateBucket);
        Storage::forgetDisk($diskName);
    }
})->with([
    'public media without a private bucket' => ['r2', 'CUS-00001/avatar/photo.webp', null],
    'chat media without a private bucket' => ['r2_private', 'chat-media/80c6675a-a246-4909-baf6-9ec892f6fc37/photo.webp', null],
    'public media with an obsolete private bucket' => ['r2', 'CUS-00001/avatar/photo.webp', 'missing-private-test'],
    'chat media with an obsolete private bucket' => ['r2_private', 'chat-media/80c6675a-a246-4909-baf6-9ec892f6fc37/photo.webp', 'missing-private-test'],
]);

it('keeps local media files under their separate local storage directories', function (): void {
    $environment = Env::getRepository();
    $previousDriver = $environment->get('R2_DRIVER');
    $previousPrivateDriver = $environment->get('R2_PRIVATE_DRIVER');

    try {
        $environment->set('R2_DRIVER', 'local');
        $environment->clear('R2_PRIVATE_DRIVER');
        $filesystems = require config_path('filesystems.php');

        expect($filesystems['disks']['r2']['root'])->toBe(storage_path('app/r2'));
        expect($filesystems['disks']['r2_private']['root'])->toBe(storage_path('app/r2-private'));
    } finally {
        $previousDriver === null
            ? $environment->clear('R2_DRIVER')
            : $environment->set('R2_DRIVER', $previousDriver);
        $previousPrivateDriver === null
            ? $environment->clear('R2_PRIVATE_DRIVER')
            : $environment->set('R2_PRIVATE_DRIVER', $previousPrivateDriver);
    }
});
