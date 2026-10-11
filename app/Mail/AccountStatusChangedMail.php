<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $fullName,
        public readonly string $email,
        public readonly bool $isActive,
        public readonly ?string $lockReason,
        public readonly string $signInUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isActive
                ? 'Tài khoản của bạn đã được mở khóa'
                : 'Tài khoản của bạn đã bị khóa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.account-status-changed',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
