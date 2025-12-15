<?php

namespace App\Console\Commands;

use App\Models\Bail;
use App\Models\Bien;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckLeaseExpiration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'leases:check-expiration';

    /**
     * The console command description.
     */
    protected $description = 'Check for expired leases and those expiring soon, update statuses and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking lease expirations...');

        $today = Carbon::today();
        $in30Days = Carbon::today()->addDays(30);
        $in7Days = Carbon::today()->addDays(7);

        // 1. Mark expired leases (date_fin < today and still 'actif')
        $expiredLeases = Bail::where('statut', 'actif')
            ->where('date_fin', '<', $today)
            ->with(['bien', 'locataire.user', 'agence.user', 'bien.bailleur.user'])
            ->get();

        foreach ($expiredLeases as $lease) {
            // Update lease status
            $lease->update(['statut' => 'expire']);

            // Update property status to available
            if ($lease->bien) {
                $lease->bien->update(['statut' => 'disponible']);
            }

            // Notify agency
            if ($lease->agence && $lease->agence->user) {
                Notification::create([
                    'user_id' => $lease->agence->user->id,
                    'titre' => 'Bail expiré',
                    'message' => "Le bail pour le bien {$lease->bien->reference} ({$lease->locataire->user->prenom} {$lease->locataire->user->nom}) a expiré.",
                    'type' => 'bail_expire',
                    'date_envoi' => now(),
                    'lue' => false,
                    'metadata' => [
                        'bail_id' => $lease->id,
                        'bien_id' => $lease->bien_id,
                        'locataire_id' => $lease->locataire_id
                    ]
                ]);
            }

            // Notify landlord
            if ($lease->bien && $lease->bien->bailleur && $lease->bien->bailleur->user) {
                Notification::create([
                    'user_id' => $lease->bien->bailleur->user->id,
                    'titre' => 'Bail expiré',
                    'message' => "Le bail pour votre bien {$lease->bien->reference} a expiré. Le locataire était {$lease->locataire->user->prenom} {$lease->locataire->user->nom}.",
                    'type' => 'bail_expire',
                    'date_envoi' => now(),
                    'lue' => false,
                    'metadata' => [
                        'bail_id' => $lease->id,
                        'bien_id' => $lease->bien_id
                    ]
                ]);
            }

            $this->info("Marked lease #{$lease->id} as expired");
        }

        // 2. Notify about leases expiring in 7 days (early warning)
        $expiringIn7Days = Bail::where('statut', 'actif')
            ->whereDate('date_fin', $in7Days)
            ->with(['bien', 'locataire.user', 'agence.user', 'bien.bailleur.user'])
            ->get();

        foreach ($expiringIn7Days as $lease) {
            // Notify agency
            if ($lease->agence && $lease->agence->user) {
                // Check if notification already sent
                $exists = Notification::where('user_id', $lease->agence->user->id)
                    ->where('type', 'bail_expiration_7j')
                    ->where('metadata->bail_id', $lease->id)
                    ->exists();

                if (!$exists) {
                    Notification::create([
                        'user_id' => $lease->agence->user->id,
                        'titre' => 'Bail expire dans 7 jours',
                        'message' => "Le bail pour le bien {$lease->bien->reference} ({$lease->locataire->user->prenom} {$lease->locataire->user->nom}) expire le {$lease->date_fin->format('d/m/Y')}.",
                        'type' => 'bail_expiration_7j',
                        'date_envoi' => now(),
                        'lue' => false,
                        'metadata' => [
                            'bail_id' => $lease->id,
                            'bien_id' => $lease->bien_id,
                            'date_fin' => $lease->date_fin->toDateString()
                        ]
                    ]);
                    $this->info("Sent 7-day warning for lease #{$lease->id}");
                }
            }
        }

        // 3. Notify about leases expiring in 30 days
        $expiringIn30Days = Bail::where('statut', 'actif')
            ->whereDate('date_fin', $in30Days)
            ->with(['bien', 'locataire.user', 'agence.user', 'bien.bailleur.user'])
            ->get();

        foreach ($expiringIn30Days as $lease) {
            // Notify agency
            if ($lease->agence && $lease->agence->user) {
                $exists = Notification::where('user_id', $lease->agence->user->id)
                    ->where('type', 'bail_expiration_30j')
                    ->where('metadata->bail_id', $lease->id)
                    ->exists();

                if (!$exists) {
                    Notification::create([
                        'user_id' => $lease->agence->user->id,
                        'titre' => 'Bail expire dans 30 jours',
                        'message' => "Le bail pour le bien {$lease->bien->reference} ({$lease->locataire->user->prenom} {$lease->locataire->user->nom}) expire le {$lease->date_fin->format('d/m/Y')}. Pensez au renouvellement.",
                        'type' => 'bail_expiration_30j',
                        'date_envoi' => now(),
                        'lue' => false,
                        'metadata' => [
                            'bail_id' => $lease->id,
                            'bien_id' => $lease->bien_id,
                            'date_fin' => $lease->date_fin->toDateString()
                        ]
                    ]);
                    $this->info("Sent 30-day warning for lease #{$lease->id}");
                }
            }
        }

        $this->info("Lease expiration check completed. Expired: {$expiredLeases->count()}, 7-day warnings: {$expiringIn7Days->count()}, 30-day warnings: {$expiringIn30Days->count()}");

        return Command::SUCCESS;
    }
}
