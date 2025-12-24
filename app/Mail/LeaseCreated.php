<?php

namespace App\Mail;

use App\Models\Bail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class LeaseCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $bail;
    public $tenant;
    public $contractPdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Bail $bail, User $tenant, string $contractPdfPath)
    {
        $this->bail = $bail;
        $this->tenant = $tenant;
        $this->contractPdfPath = $contractPdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouveau bail créé - ' . $this->bail->bien->reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.lease-created',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->contractPdfPath)
                ->as('Contrat_Bail_' . $this->bail->bien->reference . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
