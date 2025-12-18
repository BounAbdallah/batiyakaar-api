<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordNotificationService
{
    protected $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('services.discord.webhook_url');
    }

    /**
     * Send a notification about a new custom plan request
     */
    public function notifyNewCustomPlanRequest($request)
    {
        if (!$this->webhookUrl) {
            Log::warning('Discord webhook URL not configured');
            return false;
        }

        $embed = [
            'title' => '🎯 Nouvelle Demande de Plan Personnalisé',
            'description' => 'Une nouvelle demande de plan sur mesure a été soumise.',
            'color' => 3447003, // Blue color
            'fields' => [
                [
                    'name' => '👤 Client',
                    'value' => "{$request->prenom} {$request->nom}",
                    'inline' => true
                ],
                [
                    'name' => '📧 Email',
                    'value' => $request->email,
                    'inline' => true
                ],
                [
                    'name' => '🏢 Entreprise',
                    'value' => $request->entreprise ?: 'Non renseigné',
                    'inline' => true
                ],
                [
                    'name' => '📞 Téléphone',
                    'value' => $request->telephone ?: 'Non renseigné',
                    'inline' => true
                ],
                [
                    'name' => '🏠 Nombre de biens',
                    'value' => (string) $request->nombre_biens,
                    'inline' => true
                ],
                [
                    'name' => '👥 Nombre d\'utilisateurs',
                    'value' => (string) $request->nombre_utilisateurs,
                    'inline' => true
                ],
                [
                    'name' => '💰 Budget mensuel',
                    'value' => $request->budget_mensuel
                        ? number_format($request->budget_mensuel, 0, ',', ' ') . ' FCFA'
                        : 'Non spécifié',
                    'inline' => false
                ],
                [
                    'name' => '✨ Fonctionnalités souhaitées',
                    'value' => is_array($request->fonctionnalites_souhaitees)
                        ? implode(', ', array_slice($request->fonctionnalites_souhaitees, 0, 5)) .
                        (count($request->fonctionnalites_souhaitees) > 5 ? '...' : '')
                        : 'Aucune',
                    'inline' => false
                ],
            ],
            'footer' => [
                'text' => 'Batiyakaar - Système de gestion immobilière'
            ],
            'timestamp' => now()->toIso8601String()
        ];

        if ($request->besoins_specifiques) {
            $embed['fields'][] = [
                'name' => '📝 Besoins spécifiques',
                'value' => strlen($request->besoins_specifiques) > 200
                    ? substr($request->besoins_specifiques, 0, 200) . '...'
                    : $request->besoins_specifiques,
                'inline' => false
            ];
        }

        $payload = [
            'content' => '@here Nouvelle demande de plan personnalisé !',
            'embeds' => [$embed]
        ];

        try {
            $response = Http::post($this->webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Discord notification sent successfully for custom plan request #' . $request->id);
                return true;
            } else {
                Log::error('Failed to send Discord notification', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending Discord notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a notification about a custom plan approval
     */
    public function notifyCustomPlanApproved($request, $plan)
    {
        if (!$this->webhookUrl) {
            return false;
        }

        $embed = [
            'title' => '✅ Plan Personnalisé Approuvé',
            'description' => "Le plan personnalisé pour **{$request->prenom} {$request->nom}** a été créé et approuvé.",
            'color' => 3066993, // Green color
            'fields' => [
                [
                    'name' => '📦 Nom du plan',
                    'value' => $plan->nom,
                    'inline' => true
                ],
                [
                    'name' => '💵 Prix mensuel',
                    'value' => number_format($plan->prix_mensuel, 0, ',', ' ') . ' FCFA',
                    'inline' => true
                ],
                [
                    'name' => '📧 Client',
                    'value' => $request->email,
                    'inline' => false
                ],
            ],
            'footer' => [
                'text' => 'Batiyakaar - Système de gestion immobilière'
            ],
            'timestamp' => now()->toIso8601String()
        ];

        $payload = [
            'embeds' => [$embed]
        ];

        try {
            Http::post($this->webhookUrl, $payload);
            return true;
        } catch (\Exception $e) {
            Log::error('Exception while sending Discord approval notification: ' . $e->getMessage());
            return false;
        }
    }
}
