<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSubscriptionRequest extends Notification
{
    use Queueable;

    private $agency;
    private $plan;

    /**
     * Create a new notification instance.
     */
    public function __construct($agency, $plan)
    {
        $this->agency = $agency;
        $this->plan = $plan;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [\App\Channels\InternalNotificationChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toInternalNotification(object $notifiable): array
    {
        return [
            'title' => 'Nouvelle demande d\'abonnement',
            'message' => "L'agence {$this->agency->raison_sociale} souhaite souscrire au plan {$this->plan->nom}.",
            'type' => 'systeme',
            'link' => "/admin/agencies/{$this->agency->id}",
            'notification_type' => 'subscription_request',
            'agency_id' => $this->agency->id,
            'plan_id' => $this->plan->id,
        ];
    }
}
