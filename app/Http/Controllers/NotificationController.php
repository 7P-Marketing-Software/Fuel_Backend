<?php

namespace App\Http\Controllers;

use App\Models\Notification;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;


class NotificationController extends Controller
{

    public function sendTopicNotification($sender_id, $sender_type, $title, $body, string $recipient_type, ?int $recipient_id = null)
    {
        $path = storage_path('app/private/firebase/el7a2ny_credentials.json');
        $firebase = (new Factory)->withServiceAccount($path);
        $messaging = $firebase->createMessaging();

        $topic = "ALHAGNI";
        if (in_array($recipient_type, ['customer', 'store', 'delivery'])) {
            $topic .= ".{$recipient_type}";
        }
        if ($recipient_id) {
            $topic .= ".{$recipient_id}";
        }

        $message = CloudMessage::fromArray([
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'topic' => $topic,
            'priority' => 'high',
        ]);

        $notifications = [];

        try {
            $messaging->send($message);
            $notifications[] = Notification::create([
                'sender_id' => $sender_id,
                'sender_type' => $sender_type,
                'title' => $title,
                'body' => $body,
                'recipient_id' => $recipient_id,
                'recipient_type' => $recipient_type,
            ]);
        } catch (MessagingException $e) {
            Log::error("Firebase Messaging Error: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("Notification Creation Error: " . $e->getMessage());
        }
        return $notifications;
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
