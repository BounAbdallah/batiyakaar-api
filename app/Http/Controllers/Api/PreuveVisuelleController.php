<?php

namespace App\Http\Controllers\Api;

use App\Models\PreuveVisuelle;
use Illuminate\Http\Request;

class PreuveVisuelleController extends Controller
{
    /**
     * Store a new proof (upload)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'etape_id' => 'required|exists:etapes,id',
            'type' => 'required|in:photo,video', // DB only supports photo/video
            'fichier' => 'required|file|mimes:jpg,jpeg,png,mp4|max:20480',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // Resolve Entrepreneur ID (MVP: Create if missing for current user)
        $user = $request->user();
        $entrepreneur = \App\Models\Entrepreneur::firstOrCreate(['user_id' => $user->id]);
        $validated['entrepreneur_id'] = $entrepreneur->id;

        if ($request->hasFile('fichier')) {
            $path = $request->file('fichier')->store('preuves', 'public');
            $validated['url_fichier'] = asset('storage/' . $path);
        }

        $validated['horodatage'] = now();
        $validated['hash_certification'] = hash('sha256', $validated['url_fichier'] . now());
        $validated['validee'] = false;

        $preuve = PreuveVisuelle::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Preuve ajoutée et certifiée avec succès',
            'data' => $preuve
        ], 201);
    }

    /**
     * Get proofs for a specific step
     */
    public function index(Request $request)
    {
        $query = PreuveVisuelle::query();

        if ($request->has('etape_id')) {
            $query->where('etape_id', $request->etape_id);
        }

        $preuves = $query->with('user')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $preuves
        ]);
    }
}
