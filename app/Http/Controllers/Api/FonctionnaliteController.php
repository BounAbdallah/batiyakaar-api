<?php

namespace App\Http\Controllers\Api;

use App\Models\Fonctionnalite;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class FonctionnaliteController extends Controller
{
    /**
     * Display a listing of all features (Admin only)
     */
    public function index()
    {
        $fonctionnalites = Fonctionnalite::orderBy('ordre')->get();

        return response()->json([
            'success' => true,
            'data' => $fonctionnalites
        ]);
    }

    /**
     * Store a newly created feature (Admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:fonctionnalites,code|max:255',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module' => 'nullable|string|max:255',
            'icone' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:255',
            'actif' => 'boolean',
            'ordre' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $fonctionnalite = Fonctionnalite::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fonctionnalité créée avec succès',
            'data' => $fonctionnalite
        ], 201);
    }

    /**
     * Display the specified feature
     */
    public function show($id)
    {
        $fonctionnalite = Fonctionnalite::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fonctionnalite
        ]);
    }

    /**
     * Update the specified feature (Admin only)
     */
    public function update(Request $request, $id)
    {
        $fonctionnalite = Fonctionnalite::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|unique:fonctionnalites,code,' . $id . '|max:255',
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'module' => 'nullable|string|max:255',
            'icone' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:255',
            'actif' => 'boolean',
            'ordre' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $fonctionnalite->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fonctionnalité mise à jour avec succès',
            'data' => $fonctionnalite
        ]);
    }

    /**
     * Remove the specified feature (Admin only)
     */
    public function destroy($id)
    {
        $fonctionnalite = Fonctionnalite::findOrFail($id);

        // Check if feature is associated with any plans
        if ($fonctionnalite->plans()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette fonctionnalité car elle est associée à des plans'
            ], 400);
        }

        $fonctionnalite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fonctionnalité supprimée avec succès'
        ]);
    }
}
