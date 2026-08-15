<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantAdminWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenantName,
        public string $resetUrl,
        public string $adminEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Votre espace {$this->tenantName} est prêt",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-admin-welcome',
            text: 'emails.tenant-admin-welcome-text',
        );
    }
}
