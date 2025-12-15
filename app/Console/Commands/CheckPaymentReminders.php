<?php

namespace App\Console\Commands;

use App\Models\Bail;
use App\Models\PaiementLoyer;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payments:check-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Check for unpaid rents and send payment reminders to agencies and landlords';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking payment reminders...');

        $today = Carbon::today();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        // Get all active leases
        $activeLeases = Bail::where('statut', 'actif')
            ->with(['bien.bailleur.user', 'agence.user', 'locataire.user'])
            ->get();

        $unpaidCount = 0;
        $partialCount = 0;

        foreach ($activeLeases as $lease) {
            // Check if there's a payment for this month
            $payment = PaiementLoyer::where('bail_id', $lease->id)
                ->whereYear('date_paiement', $currentYear)
                ->whereMonth('date_paiement', $currentMonth)
                ->first();

            $hasUnpaidIssue = false;
            $isPartial = false;
            $message = '';
            $notificationType = '';

            if (!$payment) {
                // No payment at all for this month
                // Only notify after the 5th of the month
                if ($today->day >= 5) {
                    $hasUnpaidIssue = true;
                    $message = "Aucun paiement de loyer reçu pour le mois de " . $today->translatedFormat('F Y') . " pour le bien {$lease->bien->reference} (Locataire: {$lease->locataire->user->prenom} {$lease->locataire->user->nom}).";
                    $notificationType = 'paiement_retard';
                    $unpaidCount++;
                }
            } elseif ($payment->statut === 'partiel' || $payment->statut === 'en_retard') {
                // Partial payment
                $hasUnpaidIssue = true;
                $isPartial = true;
                $remaining = $lease->loyer_mensuel - $payment->montant;
                $message = "Paiement partiel de {$payment->montant} FCFA reçu pour le bien {$lease->bien->reference}. Reste à payer: {$remaining} FCFA.";
                $notificationType = 'paiement_partiel';
                $partialCount++;
            }

            if ($hasUnpaidIssue) {
                // Check if we already sent a notification this week for this lease
                $existingNotification = Notification::where('type', $notificationType)
                    ->where('metadata->bail_id', $lease->id)
                    ->where('metadata->month', $currentMonth)
                    ->where('metadata->year', $currentYear)
                    ->where('created_at', '>=', $today->copy()->subDays(7))
                    ->exists();

                if (!$existingNotification) {
                    // Notify agency
                    if ($lease->agence && $lease->agence->user) {
                        Notification::create([
                            'user_id' => $lease->agence->user->id,
                            'titre' => $isPartial ? 'Paiement partiel' : 'Loyer en retard',
                            'message' => $message,
                            'type' => $notificationType,
                            'date_envoi' => now(),
                            'lue' => false,
                            'metadata' => [
                                'bail_id' => $lease->id,
                                'bien_id' => $lease->bien_id,
                                'locataire_id' => $lease->locataire_id,
                                'month' => $currentMonth,
                                'year' => $currentYear,
                                'loyer_attendu' => $lease->loyer_mensuel
                            ]
                        ]);
                    }

                    // Notify landlord
                    if ($lease->bien && $lease->bien->bailleur && $lease->bien->bailleur->user) {
                        Notification::create([
                            'user_id' => $lease->bien->bailleur->user->id,
                            'titre' => $isPartial ? 'Paiement partiel reçu' : 'Loyer non reçu',
                            'message' => $message,
                            'type' => $notificationType,
                            'date_envoi' => now(),
                            'lue' => false,
                            'metadata' => [
                                'bail_id' => $lease->id,
                                'bien_id' => $lease->bien_id,
                                'month' => $currentMonth,
                                'year' => $currentYear
                            ]
                        ]);
                    }

                    $this->info("Sent reminder for lease #{$lease->id}");
                }
            }
        }

        $this->info("Payment reminders check completed. Unpaid: {$unpaidCount}, Partial: {$partialCount}");

        return Command::SUCCESS;
    }
}
