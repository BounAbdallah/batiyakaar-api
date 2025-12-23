<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    private $aiServiceUrl;

    public function __construct()
    {
        $this->aiServiceUrl = env('AI_SERVICE_URL', 'http://localhost:8001');
    }

    /**
     * Chat with AI assistant
     */
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string'
        ]);

        $user = $request->user();
        $agenceId = $user->agence ? $user->agence->id : $user->agence_id;

        try {
            // Call Python AI service
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $request->bearerToken()
                ])
                ->post("{$this->aiServiceUrl}/chat", [
                    'message' => $validated['message'],
                    'user_id' => $user->id,
                    'agence_id' => $agenceId,
                    'conversation_id' => $validated['conversation_id'] ?? null
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Log conversation for analytics
                Log::info('AI Chat', [
                    'user_id' => $user->id,
                    'query_type' => $data['query_type'] ?? 'unknown',
                    'message_length' => strlen($validated['message'])
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            throw new \Exception('AI service error: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('AI Chat Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get suggested questions
     */
    public function suggestions(Request $request)
    {
        $user = $request->user();
        $agenceId = $user->agence ? $user->agence->id : $user->agence_id;

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $request->bearerToken()
                ])
                ->get("{$this->aiServiceUrl}/suggestions", [
                    'user_id' => $user->id,
                    'agence_id' => $agenceId
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            }

            throw new \Exception('AI service error');

        } catch (\Exception $e) {
            Log::error('AI Suggestions Error', ['error' => $e->getMessage()]);

            // Return default suggestions on error
            return response()->json([
                'success' => true,
                'data' => [
                    'suggestions' => [
                        'Combien de biens ai-je ?',
                        'Liste des locataires en retard',
                        'Revenus du mois'
                    ]
                ]
            ]);
        }
    }

    /**
     * Health check for AI service
     */
    public function health()
    {
        try {
            $response = Http::timeout(5)->get("{$this->aiServiceUrl}/health");

            return response()->json([
                'success' => $response->successful(),
                'data' => $response->json()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI service is not available',
                'error' => $e->getMessage()
            ], 503);
        }
    }
}
