<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgencyPaymentNotification extends Notification
{
    use Queueable;

    public $bail;
    public $amount;

    /**
     * Create a new notification instance.
     */
    public function __construct($bail, $amount)
    {
        $this->bail = $bail;
        $this->amount = $amount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau Paiement Wave Reçu')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line("Un paiement de loyer a été reçu via Wave.")
            ->line("Bien : " . $this->bail->bien->reference)
            ->line("Locataire : " . $this->bail->locataire->user->nom . ' ' . $this->bail->locataire->user->prenom)
            ->line("Montant : " . number_format($this->amount, 0, ',', ' ') . ' FCFA')
            ->action('Voir les paiements', url('/dashboard/payments'))
            ->line('Merci d\'utiliser Bâti Yakaar.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Paiement Wave Reçu',
            'message' => "Paiement de " . number_format($this->amount, 0, ',', ' ') . " FCFA pour le bien " . $this->bail->bien->reference,
            'bail_id' => $this->bail->id,
            'amount' => $this->amount,
            'type' => 'payment_received'
        ];
    }
}
