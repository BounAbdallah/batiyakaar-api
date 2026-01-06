<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Ventilation;

class CommissionEarned extends Notification
{
    use Queueable;

    public $ventilation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ventilation $ventilation)
    {
        $this->ventilation = $ventilation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $paiement = $this->ventilation->paiementLoyer;
        $bail = $paiement->bail;

        return [
            'title' => 'Commission Perçue',
            'message' => "Nouvelle commission de {$this->ventilation->montant_plateforme} FCFA sur le paiement de {$bail->locataire->user->prenom} {$bail->locataire->user->nom}",
            'amount' => $this->ventilation->montant_plateforme,
            'transaction_ref' => $paiement->reference_transaction,
            'property' => $bail->bien->reference ?? 'Bien Inconnu',
            'type' => 'commission_earned'
        ];
    }
}
