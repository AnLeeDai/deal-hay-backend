<?php

use App\Services\MediaClient;
use Illuminate\Support\Facades\Storage;

it('redirects public avatar and product keys to the configured media host', function (string $path): void {
    config()->set('filesystems.disks.r2.url', 'https://media.example.test');

    $response = $this->get('/api/media/assets/'.rawurlencode($path));

    $response->assertRedirect('https://media.example.test/'.$path);
})->with([
    'system avatar' => 'systems_avatar/systems_users_AV1.webp',
    'user avatar' => 'legacy_user_42/avatar/example.webp',
    'product' => 'legacy-user-code/products/example.webp',
]);

it('returns 422 for unsafe or non-public media keys', function (string $path): void {
    config()->set('filesystems.disks.r2.url', 'https://media.example.test');

    $response = $this->getJson('/api/media/assets/'.rawurlencode($path));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('path')
        ->assertJsonPath('errors.path.0', 'The requested media path is not publicly accessible.')
        ->assertHeaderMissing('Location');
})->with([
    'external HTTPS URL' => 'https://outside.example.test/image.webp',
    'external HTTP URL' => 'http://outside.example.test/image.webp',
    'protocol relative URL' => '//outside.example.test/image.webp',
    'absolute filesystem key' => '/home/private/image.webp',
    'parent traversal' => 'seller/avatar/../private.webp',
    'current directory segment' => 'seller/./avatar.webp',
    'encoded parent traversal' => 'seller/%2e%2e/private.webp',
    'double encoded parent traversal' => 'seller/%252e%252e/private.webp',
    'backslash' => 'seller\\avatar.webp',
    'control character' => "seller/avatar\r.webp",
    'query string' => 'seller/avatar.webp?token=secret',
    'fragment' => 'seller/avatar.webp#fragment',
    'private attachment' => 'chat-media/conversation/image.webp',
    'staged upload' => 'staged-images/owner/image.webp',
    'encoded private attachment' => 'chat-media%2fconversation%2fimage.webp',
]);

it('keeps public media references relative including historical backend asset URLs', function (): void {
    config()->set('app.url', 'https://private-api.example.test');
    $media = app(MediaClient::class);
    expect($media->url('owner/products/image.webp'))->toBe('/api/media/assets/owner/products/image.webp')
        ->and($media->url('https://private-api.example.test/api/media/assets/owner/products/image.webp'))->toBe('/api/media/assets/owner/products/image.webp')
        ->and($media->url('https://private-api.example.test/storage/r2/owner/avatar/image.webp'))->toBe('/api/media/assets/owner/avatar/image.webp')
        ->and($media->url('https://external.example.test/image.webp'))->toBe('https://external.example.test/image.webp');
});

it('streams public images unchanged without returning an upstream URL', function (): void {
    $disk = Storage::fake('r2');
    $path = 'owner/products/image.webp';
    $bytes = file_get_contents(base_path('tests/Fixtures/prepared.webp'));
    $disk->put($path, $bytes);
    $response = $this->withHeader('X-DealHay-Media-Transport', 'stream')->get('/api/media/assets/'.$path);
    $response->assertOk()->assertHeader('Content-Type', 'image/webp')
        ->assertHeader('Content-Length', (string) strlen($bytes))
        ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeaderMissing('Location');
    expect($response->streamedContent())->toBe($bytes);
});

it('refuses missing oversized unsupported and private assets in stream mode', function (string $path, ?string $bytes, int $status): void {
    $disk = Storage::fake('r2');
    if ($bytes !== null) {
        $disk->put($path, $bytes);
    }
    $this->withHeader('X-DealHay-Media-Transport', 'stream')->getJson('/api/media/assets/'.$path)
        ->assertStatus($status)->assertHeaderMissing('Location');
})->with([
    'missing' => ['owner/products/missing.webp', null, 404],
    'oversized' => ['owner/products/large.webp', str_repeat('x', 3 * 1024 * 1024 + 1), 404],
    'active content' => ['owner/products/image.svg', '<svg/>', 404],
    'private chat' => ['chat-media/conversation/image.webp', 'private', 422],
    'staging' => ['staged-images/owner/image.webp', 'staged', 422],
]);
