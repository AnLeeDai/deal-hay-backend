<?php

use Aws\MockHandler;
use Aws\Result;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Storage;

it('writes canonical R2 object keys without a local filesystem prefix', function (string $diskName, string $bucket): void {
    $environment = Env::getRepository();
    $previousDriver = $environment->get('R2_DRIVER');
    $previousPrivateDriver = $environment->get('R2_PRIVATE_DRIVER');

    try {
        $environment->set('R2_DRIVER', 's3');
        $environment->clear('R2_PRIVATE_DRIVER');
        $filesystems = require config_path('filesystems.php');
        config([
            'filesystems.disks.'.$diskName => array_replace($filesystems['disks'][$diskName], [
                'key' => 'test-key',
                'secret' => 'test-secret',
                'bucket' => $bucket,
                'region' => 'auto',
                'endpoint' => 'https://r2.example.test',
            ]),
        ]);
        Storage::forgetDisk($diskName);
        $disk = Storage::disk($diskName);
        $handler = new MockHandler([new Result]);
        $disk->getClient()->getHandlerList()->setHandler($handler);

        $written = $disk->put('CUS-00001/avatar/photo.webp', 'image-bytes', ['ContentType' => 'image/webp']);

        expect($written)->toBeTrue();
        expect($handler->getLastCommand()['Key'])->toBe('CUS-00001/avatar/photo.webp');
        expect($handler->getLastRequest()->getUri()->getPath())->toBe('/'.$bucket.'/CUS-00001/avatar/photo.webp');
    } finally {
        $previousDriver === null
            ? $environment->clear('R2_DRIVER')
            : $environment->set('R2_DRIVER', $previousDriver);
        $previousPrivateDriver === null
            ? $environment->clear('R2_PRIVATE_DRIVER')
            : $environment->set('R2_PRIVATE_DRIVER', $previousPrivateDriver);
        Storage::forgetDisk($diskName);
    }
})->with([
    'public bucket' => ['r2', 'public-test'],
    'private bucket' => ['r2_private', 'private-test'],
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
