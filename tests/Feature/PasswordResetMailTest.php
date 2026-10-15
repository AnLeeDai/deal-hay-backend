<?php

use App\Mail\ResetPasswordSuccessMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;

it('sends a link to the frontend reset page and resets the password using its query parameters', function (): void {
    Event::fake([PasswordResetLinkSent::class]);
    Notification::fake();
    Mail::fake();
    config(['app.frontend_url' => 'https://deal-hay.example.test/']);

    $user = User::factory()->customer()->create([
        'email' => 'reset.customer+tag@example.com',
        'password' => 'Production!123',
    ]);
    $user->createToken('existing-device');

    $this->postJson('/api/auth/forgot-password', [
        'email' => strtoupper($user->email),
    ])->assertOk();

    Event::assertDispatchedTimes(PasswordResetLinkSent::class, 1);
    Event::assertDispatched(PasswordResetLinkSent::class, fn (PasswordResetLinkSent $event): bool => $event->user->is($user));
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
    $notification = Notification::sent($user, ResetPassword::class)->sole();
    $resetUrl = $notification->toMail($user)->actionUrl;

    expect($resetUrl)->toBe('https://deal-hay.example.test/reset-password?'.http_build_query([
        'token' => $notification->token,
        'email' => $user->email,
    ]));

    parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);

    $this->postJson('/api/auth/reset-password', [
        ...$query,
        'password' => 'Production!456',
        'password_confirmation' => 'Production!456',
    ])->assertOk();

    expect(Hash::check('Production!456', $user->refresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => $user->getEmailForPasswordReset(),
    ]);
    Mail::assertQueued(ResetPasswordSuccessMail::class, fn (ResetPasswordSuccessMail $mail): bool => $mail->hasTo($user->email));
});

it('keeps the forgot password response generic for an unknown email', function (): void {
    Event::fake([PasswordResetLinkSent::class]);
    Notification::fake();

    $this->postJson('/api/auth/forgot-password', [
        'email' => 'unknown.customer@example.com',
    ])->assertOk()
        ->assertJsonPath('message', 'Nếu thông tin tài khoản hợp lệ, liên kết đặt lại mật khẩu sẽ được gửi qua email.');

    Notification::assertNothingSent();
    Event::assertNotDispatched(PasswordResetLinkSent::class);
    $this->assertDatabaseCount('password_reset_tokens', 0);
});

it('allows an immediate retry after the reset email fails to send', function (): void {
    Event::fake([PasswordResetLinkSent::class]);
    Exceptions::fake();
    config(['mail.default' => 'array']);

    $user = User::factory()->customer()->create();
    $transport = Mockery::mock(TransportInterface::class);
    $transport->shouldReceive('send')->once()
        ->andThrow(new TransportException('Simulated mail transport failure.'));
    $mailer = Mail::mailer();
    $mailer->setSymfonyTransport($transport);

    $this->postJson('/api/auth/forgot-password', [
        'email' => $user->email,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    Exceptions::assertReported(TransportException::class);
    Event::assertNotDispatched(PasswordResetLinkSent::class);
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => $user->getEmailForPasswordReset(),
    ]);

    $retryTransport = new ArrayTransport;
    $mailer->setSymfonyTransport($retryTransport);

    $this->postJson('/api/auth/forgot-password', [
        'email' => $user->email,
    ])->assertOk();

    Event::assertDispatchedTimes(PasswordResetLinkSent::class, 1);
    Event::assertDispatched(PasswordResetLinkSent::class, fn (PasswordResetLinkSent $event): bool => $event->user->is($user));
    expect($retryTransport->messages())->toHaveCount(1);
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->getEmailForPasswordReset(),
    ]);
});
