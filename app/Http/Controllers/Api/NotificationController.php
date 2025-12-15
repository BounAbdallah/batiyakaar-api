<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use App\Models\Bail;
use App\Models\Incident;
use App\Models\Bien;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->has('lue')) {
            $query->where('lue', $request->lue === 'true');
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Get a single notification with enriched related data
     */
    public function show(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Mark as read when viewing details
        $notification->update(['lue' => true]);

        $relatedData = [];
        $metadata = $notification->metadata ?? [];

        // Fetch related data based on notification type
        if (isset($metadata['bail_id'])) {
            $bail = Bail::with(['bien.bailleur.user', 'locataire.user', 'agence.user'])
                ->find($metadata['bail_id']);
            if ($bail) {
                $relatedData['bail'] = $bail;
            }
        }

        if (isset($metadata['bien_id']) && !isset($relatedData['bail'])) {
            $bien = Bien::with(['bailleur.user'])->find($metadata['bien_id']);
            if ($bien) {
                $relatedData['bien'] = $bien;
            }
        }

        if (isset($metadata['incident_id'])) {
            $incident = Incident::with(['bail.bien', 'locataire.user', 'technicien'])
                ->find($metadata['incident_id']);
            if ($incident) {
                $relatedData['incident'] = $incident;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
            'related' => $relatedData
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('lue', false)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->update(['lue' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('lue', false)
            ->update(['lue' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications marquées comme lues'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée'
        ]);
    }
}
