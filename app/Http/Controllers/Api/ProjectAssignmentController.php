<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartiesPrenantes;
use App\Models\ProjetConstruction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectAssignmentController extends Controller
{
    /**
     * Assign a user (Entrepreneur/Agence) to a project.
     */
    public function store(Request $request, string $projectId)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'role' => 'required|string|in:entrepreneur,agence,bailleur,architecte', // Add roles as needed
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $project = ProjetConstruction::findOrFail($projectId);

            // TODO: check if auth user is the owner (bailleur) of the project

            $userToAssign = User::where('email', $request->email)->first();

            // Check if already assigned
            $exists = PartiesPrenantes::where('projet_construction_id', $project->id)
                ->where('user_id', $userToAssign->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur est déjà assigné à ce projet.'
                ], 409);
            }

            $assignment = PartiesPrenantes::create([
                'projet_construction_id' => $project->id,
                'user_id' => $userToAssign->id,
                'role' => $request->role,
                'date_ajout' => now(),
                'actif' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur assigné avec succès.',
                'data' => $assignment
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all parties for a project.
     */
    public function index(string $projectId)
    {
        try {
            $partners = PartiesPrenantes::with('user')
                ->where('projet_construction_id', $projectId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $partners
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
