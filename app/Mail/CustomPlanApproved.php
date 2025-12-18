<?php

namespace App\Mail;

use App\Models\CustomPlanRequest;
use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomPlanApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $customRequest;
    public $plan;
    public $accessUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomPlanRequest $customRequest, Plan $plan)
    {
        $this->customRequest = $customRequest;
        $this->plan = $plan;
        $this->accessUrl = config('app.frontend_url') . '/register?plan=' . $plan->id . '&token=' . $plan->access_token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre Plan Personnalisé est Prêt ! 🎉',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.custom-plan-approved',
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
