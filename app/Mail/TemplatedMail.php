<?php

namespace App\Mail;

use App\Enums\MailAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly MailAudience $audience,
        public readonly string $mailSubject,
        public readonly string $viewName,
        public readonly array $data = [],
        public readonly ?string $recipientName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name'),
            ),
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewName,
            with: array_merge($this->data, [
                'audience' => $this->audience,
                'recipientName' => $this->recipientName,
                'appName' => config('app.name'),
                'appUrl' => config('app.url'),
                'supportEmail' => config('cct_mail.support_email'),
                'layout' => $this->audience->layout(),
            ]),
        );
    }
}
