<?php

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
