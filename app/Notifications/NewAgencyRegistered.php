<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAgencyRegistered extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $user;
    public $agence;

    /**
     * Create a new notification instance.
     */
    public function __construct($user, $agence)
    {
        $this->user = $user;
        $this->agence = $agence;
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
            'title' => 'Nouvelle Agence Inscrite',
            'message' => "L'agence {$this->agence->raison_sociale} ({$this->user->email}) s'est inscrite et est en attente d'activation.",
            'type' => 'systeme',
            'action_url' => '/admin/agencies/' . $this->agence->id,
            'notification_type' => 'agency_registration', // Metadata
            'target_agency_id' => $this->agence->id
        ];
    }
}
