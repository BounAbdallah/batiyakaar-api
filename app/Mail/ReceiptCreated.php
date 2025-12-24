<?php

namespace App\Mail;

use App\Models\Quittance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ReceiptCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $quittance;
    public $tenant;
    public $receiptPdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Quittance $quittance, User $tenant, string $receiptPdfPath)
    {
        $this->quittance = $quittance;
        $this->tenant = $tenant;
        $this->receiptPdfPath = $receiptPdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle quittance - ' . $this->quittance->numero_quittance,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt-created',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->receiptPdfPath)
                ->as('Quittance_' . $this->quittance->numero_quittance . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
