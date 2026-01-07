<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Models\Notification as NotificationModel; // Assuming App\Models\Notification maps to 'notifications' table

class BatiyakaarDatabaseChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        $data = $notification->toArray($notifiable);

        NotificationModel::create([
            'user_id' => $notifiable->id,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'type' => $data['type'] ?? 'systeme', // Use the type from data or default
            'titre' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'metadata' => $data, // Store full data in metadata/data (depending on model)
            'data' => $data,    // Store in data for standard compatibility
            'lue' => false,
            'date_envoi' => now(),
        ]);
    }
}
