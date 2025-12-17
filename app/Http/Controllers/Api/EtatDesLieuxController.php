<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EtatDesLieux;
use Illuminate\Http\Request;

class EtatDesLieuxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EtatDesLieux::with(['bail.bien', 'bail.locataire.user']);

        if ($request->has('bail_id')) {
            $query->where('bail_id', $request->bail_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bail_id' => 'required|exists:baux,id',
            'type' => 'required|in:entrant,sortant',
            'date_etat_des_lieux' => 'required|date',
            'observations' => 'nullable|string',
            'content' => 'nullable|array', // Structured data
            'documents' => 'nullable|array'
        ]);

        $etat = EtatDesLieux::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'État des lieux enregistré',
            'data' => $etat
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $etat = EtatDesLieux::with(['bail.bien', 'bail.locataire.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $etat
        ]);
    }

    /**
     * Generate PDF for the specified resource.
     */
    public function generatePdf($id)
    {
        // DomPDF can be memory intensive
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $etat = EtatDesLieux::with(['bail.bien', 'bail.locataire.user', 'bail.agence', 'bail.agence.user'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.etat_des_lieux', compact('etat'));

        return $pdf->download("etat_des_lieux_{$etat->id}.pdf");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Not implemented for now (immutable typically, or simple edits)
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $etat = EtatDesLieux::findOrFail($id);
        $etat->delete();

        return response()->json([
            'success' => true,
            'message' => 'État des lieux supprimé'
        ]);
    }
}
