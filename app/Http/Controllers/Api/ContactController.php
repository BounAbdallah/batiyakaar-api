<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Mail\ContactMessageReceived;
use App\Services\DiscordNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Store a new contact message
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'entreprise' => 'nullable|string|max:255',
            'sujet' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create contact message
        $contactMessage = ContactMessage::create($request->all());

        // Send Discord notification
        try {
            $this->sendDiscordNotification($contactMessage);
        } catch (\Exception $e) {
            \Log::error('Failed to send Discord notification for contact: ' . $e->getMessage());
        }

        // Send email notification to admin
        try {
            Mail::to(config('mail.admin_email', 'admin@batiyakaar.com'))
                ->send(new ContactMessageReceived($contactMessage));
        } catch (\Exception $e) {
            \Log::error('Failed to send contact email notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.',
            'data' => $contactMessage
        ], 201);
    }

    /**
     * Get all contact messages (Admin only)
     */
    public function index(Request $request)
    {
        // Check if user is admin
        if (!$request->user() || $request->user()->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent accéder à cette ressource.'
            ], 403);
        }

        $query = ContactMessage::query();

        // Filter by status
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('sujet', 'like', "%{$search}%");
            });
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Get a single contact message (Admin only)
     */
    public function show(Request $request, $id)
    {
        // Check if user is admin
        if (!$request->user() || $request->user()->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent accéder à cette ressource.'
            ], 403);
        }

        $message = ContactMessage::findOrFail($id);

        // Mark as read
        if ($message->statut === 'nouveau') {
            $message->update(['statut' => 'lu']);
        }

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * Update contact message status (Admin only)
     */
    public function update(Request $request, $id)
    {
        // Check if user is admin
        if (!$request->user() || $request->user()->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent accéder à cette ressource.'
            ], 403);
        }

        $message = ContactMessage::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'statut' => 'sometimes|in:nouveau,lu,traite,archive',
            'reponse_admin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $message->update($request->only(['statut', 'reponse_admin']));

        return response()->json([
            'success' => true,
            'message' => 'Message mis à jour avec succès',
            'data' => $message
        ]);
    }

    /**
     * Send Discord notification for new contact message
     */
    private function sendDiscordNotification($message)
    {
        $webhookUrl = config('services.discord.webhook_url');

        if (!$webhookUrl) {
            return;
        }

        $embed = [
            'title' => '📧 Nouveau Message de Contact',
            'description' => 'Un nouveau message a été reçu via le formulaire de contact.',
            'color' => 5814783, // Purple color
            'fields' => [
                [
                    'name' => '👤 Contact',
                    'value' => "{$message->prenom} {$message->nom}",
                    'inline' => true
                ],
                [
                    'name' => '📧 Email',
                    'value' => $message->email,
                    'inline' => true
                ],
                [
                    'name' => '📞 Téléphone',
                    'value' => $message->telephone ?: 'Non renseigné',
                    'inline' => true
                ],
                [
                    'name' => '🏢 Entreprise',
                    'value' => $message->entreprise ?: 'Non renseigné',
                    'inline' => true
                ],
                [
                    'name' => '📋 Sujet',
                    'value' => $message->sujet,
                    'inline' => false
                ],
                [
                    'name' => '💬 Message',
                    'value' => strlen($message->message) > 500
                        ? substr($message->message, 0, 500) . '...'
                        : $message->message,
                    'inline' => false
                ],
            ],
            'footer' => [
                'text' => 'Batiyakaar - Formulaire de contact'
            ],
            'timestamp' => now()->toIso8601String()
        ];

        $payload = [
            'content' => '@here Nouveau message de contact !',
            'embeds' => [$embed]
        ];

        \Http::post($webhookUrl, $payload);
    }
}
