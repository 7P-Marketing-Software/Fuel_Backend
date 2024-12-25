<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function sendTopicNotification($sender_id, $sender_type, $title, $body, string $recipient_type, ?int $recipient_id = null)
    {

        if (!in_array($recipient_type, ['store', 'delivery', 'customer'])) {
            return $this->respondError('Invalid recipient type!');
        }

        $topic = "Your_project/{$recipient_type}";

        if ($recipient_id) {
            $topic .= "/{$recipient_id}";
        }

        $fields = [
            'to' => $topic,
            'priority' => 'high',
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'icon' => '',
                'image' => '',
            ],
            'data' => [
                'title' => $title,
                'message' => $body,
                'sound' => 'default',
                'icon' => '',
                'image' => '',
            ],

        ];
        $API_ACCESS_KEY = env('SERVER_API_KEY');
        $headers = [
            'Authorization: key=' . $API_ACCESS_KEY,
            'Content-Type: application/json',
        ];

        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        // $result = curl_exec($ch);
        // curl_close($ch);

        $notification = Notification::create([
            'sender_id' => $sender_id,
            'sender_type' => $sender_type,
            'recipient_id' => $recipient_id,
            'recipient_type' => $recipient_type,
            'title' => $title,
            'body' => $body,
        ]);

        return $this->respondOk($notification);
    }

    public function getNotifications()
    {
        //write your guards here :)
        $guards = ['store', 'user', 'delivery'];

        foreach ($guards as $guard) {
            if ($user = auth($guard)->user()) {
                $recipientType = $guard;
                $notifications = Notification::where('recipient_id', $user->id)
                    ->where('recipient_type', $recipientType)
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
                return $this->respondOk($notifications);
            }
        }
        return $this->respondError('Unauthenticated');
    }
}
