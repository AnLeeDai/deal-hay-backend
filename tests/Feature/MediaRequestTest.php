<?php

use App\Models\StagedImageUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('returns 401 for image upload sessions without authentication', function (): void {
    $this->postJson('/api/private/uploads/images', ['uploads' => [['size' => 100, 'content_type' => 'image/png']]])
        ->assertUnauthorized();

    $this->assertDatabaseCount('staged_image_uploads', 0);
});

it('creates an upload session for a validated image declaration without touching storage', function (): void {
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/private/uploads/images', ['uploads' => [['size' => 100, 'content_type' => 'image/png']]]);

    $response->assertOk()->assertJsonStructure(['uploads' => [['token']]]);
    $upload = StagedImageUpload::query()->sole();
    expect($upload->user_id)->toBe($user->getKey());
    expect($upload->expected_size)->toBe(100);
    expect($upload->path)->toStartWith('staged-images/'.$user->user_code.'/')->toEndWith('.source');
    expect($disk->allFiles())->toBeEmpty();
});

it('returns 422 for unsafe upload byte lengths', function (int $size, string $message): void {
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->postJson('/api/private/uploads/images', ['uploads' => [['size' => $size, 'content_type' => 'image/png']]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['uploads.0.size' => $message]);

    $this->assertDatabaseCount('staged_image_uploads', 0);
})->with([
    'empty source' => [0, 'Ảnh tải lên phải có ít nhất 1 byte.'],
    'oversized source' => [31_457_281, 'Ảnh tải lên không được vượt quá 30 MB.'],
]);

it('returns 422 for unsupported upload content types', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->postJson('/api/private/uploads/images', ['uploads' => [['size' => 100, 'content_type' => 'text/html']]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['uploads.0.content_type' => 'Ảnh tải lên phải có định dạng JPG, PNG, GIF, WEBP, AVIF hoặc BMP.']);

    $this->assertDatabaseCount('staged_image_uploads', 0);
});

it('returns 422 when upload declarations contain unexpected keys', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->postJson('/api/private/uploads/images', ['uploads' => [['size' => 100, 'content_type' => 'image/png', 'path' => 'other-owner/avatar.webp']]])
        ->assertUnprocessable()->assertJsonValidationErrors('uploads.0');

    $this->assertDatabaseCount('staged_image_uploads', 0);
});

it('returns 422 when an upload batch exceeds 51 images', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->postJson('/api/private/uploads/images', ['uploads' => array_fill(0, 52, ['size' => 100, 'content_type' => 'image/png'])])
        ->assertUnprocessable()->assertJsonValidationErrors('uploads')
        ->assertJsonPath('errors.uploads.0', 'Mỗi lần được tải tối đa 51 ảnh.');

    $this->assertDatabaseCount('staged_image_uploads', 0);
});

it('returns 422 for an invalid browser transform purpose', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->postJson('/api/private/media/transform', ['size' => 100, 'purpose' => 'unsupported'])
        ->assertUnprocessable()->assertJsonValidationErrors('purpose')
        ->assertJsonPath('errors.purpose.0', 'Mục đích tải ảnh không hợp lệ.');
});

it('returns 422 for an empty browser transform source', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->postJson('/api/private/media/transform', ['size' => 0, 'purpose' => 'product'])
        ->assertUnprocessable()->assertJsonValidationErrors('size')
        ->assertJsonPath('errors.size.0', 'Ảnh tải lên phải có ít nhất 1 byte.');
});

it('returns 403 when a customer changes a shop avatar', function (): void {
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);

    $this->post('/api/private/shop/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png')], ['Accept' => 'application/json'])
        ->assertForbidden();

    expect($user->refresh()->getRawOriginal('shop_avatar_url'))->toBeNull();
    expect($disk->allFiles())->toBeEmpty();
});

it('stores a shop owner avatar on the public image disk', function (): void {
    $disk = Storage::fake('r2');
    $user = User::factory()->shopOwner()->create();
    Sanctum::actingAs($user);

    $this->post('/api/private/shop/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png')], ['Accept' => 'application/json'])
        ->assertOk();

    $path = $user->refresh()->getRawOriginal('shop_avatar_url');
    expect($path)->toStartWith($user->user_code.'/shop-avatar/')->toEndWith('.webp');
    $disk->assertExists($path);
});

it('returns 422 when a profile avatar is not an image', function (): void {
    $disk = Storage::fake('r2');
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->post('/api/private/me/avatar', ['avatar' => UploadedFile::fake()->createWithContent('note.txt', 'Not an image.')], ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors('avatar')
        ->assertJsonPath('errors.avatar.0', 'Ảnh đại diện phải là hình ảnh hợp lệ.');

    expect($disk->allFiles())->toBeEmpty();
});

it('returns 422 when a staged source is not an image', function (): void {
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->post('/api/private/uploads/images/prepare', [
        'token' => 'invalid-token',
        'image' => UploadedFile::fake()->createWithContent('note.txt', 'Not an image.'),
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors('image')
        ->assertJsonPath('errors.image.0', 'Tệp tải lên phải là hình ảnh hợp lệ.');
});
