<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Models\Notification as InternalNotification;
use Carbon\Carbon;

class InternalNotificationChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toInternalNotification')) {
            // Fallback to standard array if specific method doesn't exist, though structure might differ
            if (method_exists($notification, 'toArray')) {
                $data = $notification->toArray($notifiable);
            } else {
                $data = ['message' => 'Notification content unavailable'];
            }
        } else {
            $data = $notification->toInternalNotification($notifiable);
        }

        // Validate required fields
        if (empty($data['title']) || empty($data['message'])) {
            return;
        }

        InternalNotification::create([
            'user_id' => $notifiable->id,
            'titre' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'] ?? 'systeme', // Default to 'systeme' if not provided
            'date_envoi' => Carbon::now(),
            'lue' => false,
            'metadata' => $data // Store full data as metadata
        ]);
    }
}
