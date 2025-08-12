<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Http;

class OneSignalService
{
    protected $appId;
    protected $apiKey;

    public function __construct()
    {
        $this->appId = env('ONESIGNAL_APP_ID');
        $this->apiKey = env('ONESIGNAL_REST_API_KEY');
    }

    private function sendRequest($data)
    {
        $response = Http::withHeaders([
            'Authorization' => "Basic {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.onesignal.com/notifications?c=push', array_merge([
            'app_id' => $this->appId,
            'priority' => 10, // High Priority
            // 'android_channel_id' => env('ONESIGNAL_ANDROID_CHANNEL_ID'), // Custom channel

        ], $data));

        return $response->json();
    }

    // Send notification to all users
    public function sendNotificationToAll($title, $message, $image = null,$extra_data=null)
    {
        $senderId = auth('sanctum')->user()->id;
        $response = $this->sendRequest([
            'included_segments' => ['All'], // Sends to all subscribed users
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'priority' => 5, // High priority
            'android_priority' => 5,
            'ttl' => 0,
            'android_channel_id' => env('ONESIGNAL_ANDROID_CHANNEL_ID'),
            'Image' => $image,
            'big_picture' => $image, // Android Image
            'ios_attachments' => ['image' => $image], // iOS Image
            'data' => [
                'page' => is_array($extra_data) && isset($extra_data['page']) ? $extra_data['page'] : null,
            ],
        ]);

        // Log::info($extra_data['page']);

        $notification = Notification::create([
            'sender_id' => $senderId,
            'title' => $title,
            'body' => $message,
            'image' => $image,
            'recipient_id' => null,
            'extra_data' => $extra_data,
        ]);

        return ['notifications' => $notification, 'status' => $response];
    }

    public function sendNotificationToUser(array $recipientIds, $title, $message,$image = null, $extra_data=null)
    {
        // Log::info('Sending notification to users: ' . implode(', ', $recipientIds));
        $image = $image ?? '';
        $senderId = auth('sanctum')->user()->id;
        $notifications = [];
        $response = Http::withHeaders([
            'Authorization' => "Basic " . env('ONESIGNAL_REST_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.onesignal.com/notifications?c=push', [
            'app_id' => env('ONESIGNAL_APP_ID'),
            'include_external_user_ids' => $recipientIds,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'priority' => 5, // Ensure high priority
            'android_priority' => 5,
            'android_channel_id' => env('ONESIGNAL_ANDROID_CHANNEL_ID'),
            'Image' => $image,
            'big_picture' => $image, // Android Image
            'ios_attachments' => ['image' => $image], // iOS Image
            'ttl' => 0, // Deliver instantly
            'data' => [
                'page' => is_array($extra_data) && isset($extra_data['page']) ? $extra_data['page'] : null,
            ],
        ]);

        foreach ($recipientIds as $recipientId) {
            $notification = Notification::create([
                'sender_id' => $senderId,
                'title' => $title,
                'body' => $message,
                'image' => $image,
                'recipient_id' => $recipientId,
                'extra_data' => $extra_data,
            ]);
            $notifications[] = [$notification];
        }

        return ['notifications' => $notifications, 'status' => json_decode($response)];
    }
}
