<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserFonctionnaliteController extends Controller
{
    /**
     * Get features available to the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $fonctionnalites = $user->getFonctionnalites();

        return response()->json([
            'success' => true,
            'data' => $fonctionnalites
        ]);
    }

    /**
     * Check if user has a specific feature
     */
    public function check(Request $request, $code)
    {
        $user = $request->user();
        $hasFeature = $user->hasFonctionnalite($code);

        return response()->json([
            'success' => true,
            'has_feature' => $hasFeature
        ]);
    }
}
