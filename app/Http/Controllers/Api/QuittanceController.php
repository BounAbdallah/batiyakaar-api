<?php

namespace App\Http\Controllers\Api;

use App\Models\Quittance;
use App\Models\PaiementLoyer;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class QuittanceController extends Controller
{
    /**
     * Display a listing of receipts - filtered by role
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Quittance::with(['paiementLoyer.bail.bien', 'paiementLoyer.bail.locataire.user']);

        // Filter by user role
        if ($user->user_type === 'locataire' && $user->locataire) {
            // Tenant sees only their receipts
            $query->whereHas('paiementLoyer.bail', function ($q) use ($user) {
                $q->where('locataire_id', $user->locataire->id);
            });
        } elseif ($user->user_type === 'agence') {
            // Agency sees receipts for their properties
            $agenceId = $user->agence ? $user->agence->id : $user->agence_id;
            if ($agenceId) {
                $query->whereHas('paiementLoyer.bail', function ($q) use ($agenceId) {
                    $q->where('agence_id', $agenceId);
                });
            }
        }
        // Admin sees all receipts

        // Pagination
        $quittances = $query->latest('date_emission')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $quittances
        ]);
    }

    /**
     * Store a newly created receipt
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'paiement_loyer_id' => 'required|exists:paiements_loyer,id',
        ]);

        $paiement = PaiementLoyer::with(['bail.locataire.user', 'bail.bien'])->findOrFail($validated['paiement_loyer_id']);

        // Check if receipt already exists
        if ($paiement->quittance) {
            return response()->json([
                'success' => false,
                'message' => 'Une quittance existe déjà pour ce paiement'
            ], 400);
        }

        // Generate receipt number
        $numero = 'Q-' . date('Y') . '-' . str_pad(Quittance::count() + 1, 6, '0', STR_PAD_LEFT);

        // Create receipt
        $quittance = Quittance::create([
            'paiement_loyer_id' => $paiement->id,
            'numero_quittance' => $numero,
            'montant' => $paiement->montant,
            'periode_debut' => $paiement->periode_debut,
            'periode_fin' => $paiement->periode_fin,
            'date_emission' => now(),
        ]);

        // Load relationships
        $quittance->load(['paiementLoyer.bail.bien', 'paiementLoyer.bail.locataire.user']);

        // Generate PDF and send email
        try {
            // Generate PDF
            $pdf = Pdf::loadView('pdfs.quittance', ['quittance' => $quittance]);
            $pdfPath = storage_path('app/temp/quittance_' . $quittance->id . '_' . time() . '.pdf');
            $pdf->save($pdfPath);

            // Send email to tenant
            if ($paiement->bail->locataire && $paiement->bail->locataire->user) {
                \Illuminate\Support\Facades\Mail::to($paiement->bail->locataire->user)->send(
                    new \App\Mail\ReceiptCreated($quittance, $paiement->bail->locataire->user, $pdfPath)
                );
            }

            // Delete temporary PDF
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send receipt email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Quittance créée avec succès',
            'data' => $quittance
        ], 201);
    }

    /**
     * Display the specified receipt
     */
    public function show(string $id)
    {
        $quittance = Quittance::with(['paiementLoyer.bail.bien', 'paiementLoyer.bail.locataire.user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $quittance
        ]);
    }

    /**
     * Download receipt PDF
     */
    public function downloadReceipt($id)
    {
        $quittance = Quittance::with(['paiementLoyer.bail.bien', 'paiementLoyer.bail.locataire.user'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdfs.quittance', ['quittance' => $quittance]);

        return $pdf->download('Quittance_' . $quittance->numero_quittance . '.pdf');
    }

    /**
     * View receipt PDF in browser
     */
    public function viewReceipt($id)
    {
        $quittance = Quittance::with(['paiementLoyer.bail.bien', 'paiementLoyer.bail.locataire.user'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdfs.quittance', ['quittance' => $quittance]);

        return $pdf->stream('Quittance_' . $quittance->numero_quittance . '.pdf');
    }

    /**
     * Remove the specified receipt
     */
    public function destroy(string $id)
    {
        $quittance = Quittance::findOrFail($id);
        $quittance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quittance supprimée avec succès'
        ]);
    }
}
