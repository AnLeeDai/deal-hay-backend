<?php

use App\Mail\EmailChangeVerificationCodeMail;
use App\Models\User;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;

dataset('email change profiles', [
    'customer email' => ['customer', '/api/private/profile/me', 'email', 'full_name', 'profile-email-change:'],
    'shop email' => ['shopOwner', '/api/private/shop/manage', 'shop_email', 'shop_name', 'shop-email-change:'],
]);

it('sends an email change code immediately with a database queue and accepts the delivered code', function (
    string $factoryState,
    string $endpoint,
    string $emailField,
    string $nameField,
    string $cachePrefix,
): void {
    Mail::fake();
    config(['queue.default' => 'database']);
    $user = User::factory()->{$factoryState}()->create([
        $emailField => 'current@example.com',
        $nameField => 'Nguyễn An',
    ]);
    Sanctum::actingAs($user);

    $this->patchJson($endpoint, [$emailField => 'new@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email_change_code']);

    Mail::assertSent(EmailChangeVerificationCodeMail::class, function (EmailChangeVerificationCodeMail $mail): bool {
        $mail->assertHasSubject('Mã xác thực thay đổi email');
        $mail->assertSeeInHtml('Nguyễn An');
        $mail->assertSeeInHtml('current@example.com');
        $mail->assertSeeInHtml('new@example.com');
        $mail->assertSeeInHtml($mail->verificationCode);
        $mail->assertSeeInHtml('Mã có hiệu lực trong 10 phút.');

        expect($mail->verificationCode)->toMatch('/^\d{6}$/');

        return $mail->hasTo('current@example.com');
    });
    Mail::assertNothingQueued();
    $this->assertDatabaseHas('users', ['id' => $user->id, $emailField => 'current@example.com']);

    $mail = Mail::sent(EmailChangeVerificationCodeMail::class)->sole();

    $this->patchJson($endpoint, [
        $emailField => 'new@example.com',
        'email_change_code' => $mail->verificationCode,
    ])->assertOk();

    $this->assertDatabaseHas('users', ['id' => $user->id, $emailField => 'new@example.com']);
    expect(Cache::has($cachePrefix.$user->id))->toBeFalse();
    Mail::assertSentCount(1);
})->with('email change profiles');

it('returns 422 and discards the email change code when the mail transport fails', function (
    string $factoryState,
    string $endpoint,
    string $emailField,
    string $nameField,
    string $cachePrefix,
): void {
    Exceptions::fake();
    config(['mail.default' => 'array', 'queue.default' => 'database']);
    $user = User::factory()->{$factoryState}()->create([
        $emailField => 'current@example.com',
        $nameField => 'Nguyễn An',
    ]);
    Sanctum::actingAs($user);
    $transport = Mockery::mock(TransportInterface::class);
    $transport->shouldReceive('send')->once()
        ->andThrow(new TransportException('Simulated email change transport failure.'));
    $mailer = Mail::mailer();
    $mailer->setSymfonyTransport($transport);

    $this->patchJson($endpoint, [$emailField => 'new@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$emailField]);

    expect(Cache::has($cachePrefix.$user->id))->toBeFalse();
    Exceptions::assertReported(TransportException::class);
    $this->assertDatabaseHas('users', ['id' => $user->id, $emailField => 'current@example.com']);
    $this->assertDatabaseCount('jobs', 0);

    $retryTransport = new ArrayTransport;
    $mailer->setSymfonyTransport($retryTransport);

    $this->patchJson($endpoint, [$emailField => 'new@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email_change_code']);

    expect($retryTransport->messages())->toHaveCount(1);
    expect(Cache::has($cachePrefix.$user->id))->toBeTrue();
})->with('email change profiles');
