<?php

namespace App\Http\Controllers\Api;

use App\Models\Visit;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Log a page visit (public route, no auth required)
     */
    public function logVisit(Request $request)
    {
        $request->validate([
            'page' => 'nullable|string|in:landing,platform,other',
        ]);

        Visit::create([
            'page'       => $request->input('page', 'other'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id'    => $request->bearerToken()
                ? optional(auth('sanctum')->user())->id
                : null,
        ]);

        return response()->json(['success' => true], 201);
    }

    /**
     * Get visit stats (admin only)
     */
    public function stats(Request $request)
    {
        $stats = [
            'total'    => Visit::count(),
            'today'    => Visit::whereDate('created_at', today())->count(),
            'landing'  => Visit::where('page', 'landing')->count(),
            'platform' => Visit::where('page', 'platform')->count(),
            'by_day'   => Visit::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }
}
