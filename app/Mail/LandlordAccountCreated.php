<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Agence;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LandlordAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $landlord;
    public $password;
    public $agence;

    /**
     * Create a new message instance.
     */
    public function __construct(User $landlord, string $password, Agence $agence)
    {
        $this->landlord = $landlord;
        $this->password = $password;
        $this->agence = $agence;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte bailleur - ' . $this->agence->raison_sociale,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.landlord-account-created',
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
