<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Agence;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $password;
    public $agence;

    /**
     * Create a new message instance.
     */
    public function __construct(User $tenant, string $password, Agence $agence)
    {
        $this->tenant = $tenant;
        $this->password = $password;
        $this->agence = $agence;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte locataire - ' . $this->agence->raison_sociale,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-account-created',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
