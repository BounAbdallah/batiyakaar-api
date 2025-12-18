<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Agence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $recipient;
    public $title;
    public $content;
    public $metadata;
    public $agence;

    /**
     * Create a new message instance.
     */
    public function __construct(User $recipient, string $title, string $content, array $metadata = [], ?Agence $agence = null)
    {
        $this->recipient = $recipient;
        $this->title = $title;
        $this->content = $content;
        $this->metadata = $metadata;
        $this->agence = $agence;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NoorImmo - ' . $this->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-reminder',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
