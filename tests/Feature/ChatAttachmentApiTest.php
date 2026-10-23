<?php

use App\Events\ChatMessageSentEvent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use League\Flysystem\UnableToWriteFile;

beforeEach(function (): void {
    config()->set('filesystems.chat_media_disk', 'r2_private');
});

it('broadcasts relative public media and attachment URLs without the backend hostname', function (): void {
    config()->set('app.url', 'https://private-api.example.test');
    $conversation = ChatConversation::factory()->create();
    $conversation->buyer->update(['avatar_url' => 'https://private-api.example.test/api/media/assets/owner/avatar/photo.webp']);
    $message = ChatMessage::factory()->create([
        'chat_conversation_id' => $conversation->getKey(),
        'sender_id' => $conversation->buyer_id,
        'attachment_mime' => 'image/webp',
        'attachment_name' => 'photo.webp',
        'attachment_size' => 100,
    ]);
    $payload = (new ChatMessageSentEvent($message))->broadcastWith();
    expect($payload['attachment']['url'])->toBe('/api/chat-attachments/'.$message->getKey())
        ->and($payload['sender']['avatar_url'])->toBe('/api/media/assets/owner/avatar/photo.webp')
        ->and(json_encode($payload))->not->toContain('private-api.example.test');
});

it('stores file attachments under the conversation UUID on the private disk', function (): void {
    $privateDisk = Storage::fake('r2_private');
    $conversation = ChatConversation::factory()->create();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);
    $clientMessageId = (string) Str::uuid();

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->post('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId,
            'attachment' => UploadedFile::fake()->createWithContent('note.txt', 'Private attachment.'),
        ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('attachment.type', 'file')
        ->assertJsonPath('attachment.name', 'note.txt')
        ->assertJsonPath('attachment.size', 19);
    $message = ChatMessage::query()->findOrFail($response->json('id'));
    $response->assertJsonPath('attachment.url', '/api/chat-attachments/'.$message->getKey());
    expect($message->attachment_disk)->toBe('r2_private');
    expect($message->attachment_path)->toStartWith('chat-media/'.$conversation->getKey().'/');
    $privateDisk->assertExists($message->attachment_path);
    expect($privateDisk->get($message->attachment_path))->toBe('Private attachment.');
    Event::assertDispatched(ChatMessageSentEvent::class, fn (ChatMessageSentEvent $event): bool => $event->message->is($message));
});

it('stores processed image attachments unchanged as private WebP objects under the conversation UUID', function (): void {
    $privateDisk = Storage::fake('r2_private');
    $conversation = ChatConversation::factory()->create();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);
    $clientMessageId = (string) Str::uuid();
    $image = preparedImage('photo.webp');
    $original = file_get_contents($image->getRealPath());

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->post('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId,
            'attachment' => $image,
        ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('attachment.type', 'image')
        ->assertJsonPath('attachment.name', 'photo.webp')
        ->assertJsonPath('attachment.mime', 'image/webp');
    $message = ChatMessage::query()->findOrFail($response->json('id'));
    expect($message->attachment_path)->toStartWith('chat-media/'.$conversation->getKey().'/')->toEndWith('.webp');
    expect($message->attachment_disk)->toBe('r2_private');
    $privateDisk->assertExists($message->attachment_path);
    expect($privateDisk->get($message->attachment_path))->toBe($original);
    expect(getimagesizefromstring($privateDisk->get($message->attachment_path))['mime'])->toBe('image/webp');
    expect($message->attachment_size)->toBe($privateDisk->size($message->attachment_path));
    Event::assertDispatched(ChatMessageSentEvent::class, fn (ChatMessageSentEvent $event): bool => $event->message->is($message));
});

it('returns 422 without saving or broadcasting a message when private image storage denies writing', function (array $messageFields): void {
    $conversation = ChatConversation::factory()->create();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);
    Exceptions::fake();
    $failure = UnableToWriteFile::atLocation('chat-media/'.$conversation->getKey().'/photo.webp', 'AccessDenied');
    $privateDisk = Mockery::mock(FilesystemAdapter::class);
    $privateDisk->shouldReceive('put')->once()->with(
        Mockery::on(fn (string $path): bool => str_starts_with($path, 'chat-media/'.$conversation->getKey().'/') && str_ends_with($path, '.webp')),
        Mockery::on(function (mixed $stream): bool {
            if (! is_resource($stream)) {
                return false;
            }
            $content = stream_get_contents($stream);
            rewind($stream);

            return (getimagesizefromstring($content)['mime'] ?? null) === 'image/webp';
        }),
        ['ContentType' => 'image/webp'],
    )->andThrow($failure);
    Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($privateDisk);
    $clientMessageId = (string) Str::uuid();

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->post('/api/messages/'.$conversation->slug, [
            ...$messageFields,
            'client_message_id' => $clientMessageId,
            'attachment' => preparedImage('photo.webp'),
        ], ['Accept' => 'application/json']);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['attachment' => 'Không thể tải ảnh lên kho lưu trữ. Vui lòng thử lại sau.']);
    $this->assertDatabaseCount('chat_messages', 0);
    Event::assertNotDispatched(ChatMessageSentEvent::class);
    Exceptions::assertReported(fn (UnableToWriteFile $exception): bool => $exception === $failure);
})->with([
    'image without caption' => [[]],
    'image with caption' => [['body' => 'Ảnh sản phẩm']],
]);

it('replays an attachment send without creating another private object or message', function (): void {
    $privateDisk = Storage::fake('r2_private');
    $conversation = ChatConversation::factory()->create();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);
    $clientMessageId = (string) Str::uuid();
    $payload = [
        'client_message_id' => $clientMessageId,
        'attachment' => UploadedFile::fake()->createWithContent('note.txt', 'Private attachment.'),
    ];
    $firstResponse = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->post('/api/messages/'.$conversation->slug, $payload, ['Accept' => 'application/json']);
    $firstResponse->assertCreated();

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->post('/api/messages/'.$conversation->slug, $payload, ['Accept' => 'application/json']);

    $response->assertOk()
        ->assertJsonPath('id', $firstResponse->json('id'))
        ->assertJsonPath('replayed', true)
        ->assertHeader('Idempotency-Replayed', 'true');
    $this->assertDatabaseCount('chat_messages', 1);
    expect($privateDisk->allFiles())->toHaveCount(1);
    Event::assertDispatchedTimes(ChatMessageSentEvent::class, 1);
});

it('streams private attachments to both conversation participants', function (string $participant): void {
    $privateDisk = Storage::fake('r2_private');
    $conversation = ChatConversation::factory()->create();
    $path = 'chat-media/'.$conversation->getKey().'/note.txt';
    $privateDisk->put($path, 'Private attachment.');
    $message = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'attachment_disk' => 'r2_private',
        'attachment_path' => $path,
        'attachment_name' => 'note.txt',
        'attachment_mime' => 'text/plain',
        'attachment_size' => 19,
    ]);
    Sanctum::actingAs($conversation->{$participant});

    $response = $this->get('/api/messages/attachments/'.$message->getKey());

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Length', '19')
        ->assertStreamedContent('Private attachment.');
})->with(['buyer' => 'buyer', 'shop owner' => 'shopOwner']);

it('returns 401 for attachment downloads without authentication', function (): void {
    $message = ChatMessage::factory()->create();

    $this->getJson('/api/messages/attachments/'.$message->getKey())->assertUnauthorized();
});

it('returns 403 for attachment downloads by an outsider', function (): void {
    $message = ChatMessage::factory()->create();
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->getJson('/api/messages/attachments/'.$message->getKey())->assertForbidden();
});

it('returns 403 for attachment downloads by a locked participant', function (): void {
    $message = ChatMessage::factory()->create();
    $buyer = $message->conversation->buyer;
    $buyer->forceFill(['is_active' => 0])->save();
    Sanctum::actingAs($buyer);

    $this->getJson('/api/messages/attachments/'.$message->getKey())->assertForbidden();
});

it('returns 404 when the private attachment object is missing', function (): void {
    $privateDisk = Storage::fake('r2_private');
    $message = ChatMessage::factory()->create([
        'attachment_disk' => 'r2_private',
        'attachment_path' => 'chat-media/missing/note.txt',
        'attachment_name' => 'note.txt',
        'attachment_mime' => 'text/plain',
        'attachment_size' => 19,
    ]);
    Sanctum::actingAs($message->conversation->buyer);

    $this->getJson('/api/messages/attachments/'.$message->getKey())->assertNotFound();

    $privateDisk->assertMissing($message->attachment_path);
});

it('removes the private upload when message persistence fails', function (): void {
    $privateDisk = Storage::fake('r2_private');
    $conversation = ChatConversation::factory()->create();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);
    Exceptions::fake();
    $failure = new RuntimeException('Message persistence failed.');
    Event::listen('eloquent.creating: '.ChatMessage::class, function () use ($failure): never {
        throw $failure;
    });
    $clientMessageId = (string) Str::uuid();

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->post('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId,
            'attachment' => UploadedFile::fake()->createWithContent('note.txt', 'Private attachment.'),
        ], ['Accept' => 'application/json']);

    $response->assertInternalServerError();
    $this->assertDatabaseCount('chat_messages', 0);
    expect($privateDisk->allFiles())->toBeEmpty();
    Event::assertNotDispatched(ChatMessageSentEvent::class);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception === $failure);
});
