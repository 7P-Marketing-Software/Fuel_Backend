<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\OneSignalService;
use Illuminate\Http\Request;

class OneSignalController extends Controller
{
    protected $oneSignalService;

    public function __construct(OneSignalService $oneSignalService)
    {
        $this->oneSignalService = $oneSignalService;
    }

    public function sendPushNotificationToAll(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'extra_data'=> 'nullable|array',
        ]);
        if ($request->hasFile('image')) {
            $file_name = $this->upload_files($request->file('image'), 'notifications/image');
            $image = $file_name;
        }
        $image = $image ?? null;
        $extraData = $request->extra_data ?? null;

        $response = $this->oneSignalService->sendNotificationToAll(
            $request->title,
            $request->message,
            $image,
            $extraData
        );
        return response()->json($response);
    }

    public function notifyUser(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'recipientIds' => 'required|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'extra_data'=> 'nullable|array',
        ]);
        $recipientIds = $request->recipientIds;
        $title =  $request->title;
        $message =  $request->message;
        $extraData = $request->extra_data ?? null;

        if ($request->hasFile('image')) {
            $file_name = $this->upload_files($request->file('image'), 'notifications/image');
            $image = $file_name;
        }
        $image = $image ?? null;
        $response = $this->oneSignalService->sendNotificationToUser($recipientIds, $title, $message, $image,$extraData);
        return $this->respondOk($response, __('messages.Notifications_sent_successfully'));
    }

    public function getAllNotifications(Request $request)
    {
        $user=auth('sanctum')->user();
        $notificationQuery = Notification::query();

        if($user->hasRole('Admin')) {
            if($request->has('recipent_id')) {
                $notificationQuery->where(function ($query) use ($request) {
                    $query->where('recipient_id', $request->recipent_id)
                        ->orWhereNull('recipient_id');
                });
            }
        } else {
            $notificationQuery->where(function ($query) use ($user) {
                $query->where('recipient_id', $user->id)
                    ->orWhereNull('recipient_id');
            });
        }

        $notifications = $notificationQuery->paginate();
        return $this->respondOk($notifications, __('messages.Notifications_retrieved_successfully'));
    }


}
