<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\DB;
use app\Services\WhatsAppService;

class WhatsAppController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function createInstance(): JsonResponse
    {
        try {
            $instanceId = $this->whatsAppService->createInstance();
            return response()->json(['instance_id' => $instanceId], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendText(Request $request)
    {

        $request->validate([
            'number' => 'required|integer',
            'message' => 'required|string',
        ]);

        try {
            $response = $this->whatsAppService->sendText($request->number, $request->message);
            return $this->respondOk($response);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function getQRCode(): JsonResponse
    {
        try {
            $response = $this->whatsAppService->getQRCode();
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function rebootInstance(): JsonResponse
    {
        try {
            $response = $this->whatsAppService->rebootInstance();
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resetInstance(): JsonResponse
    {
        try {
            $response = $this->whatsAppService->resetInstance();
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function reconnect(): JsonResponse
    {
        try {
            $response = $this->whatsAppService->reconnect();
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function setWebhook(Request $request): JsonResponse
    {
        $request->validate([
            'webhook_url' => 'required|string',
            'enable' => 'required|boolean',
        ]);

        try {
            $response = $this->whatsAppService->setWebhook($request->webhook_url, $request->enable);
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendTemplateMessage(Request $request): JsonResponse
    {
        $request->validate([
            'number' => 'required|string',
            'type' => 'required|string',
            'template' => 'required|string',
        ]);

        try {
            $response = $this->whatsAppService->sendTemplateMessage($request->number, $request->type, $request->template);
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendMedia(Request $request): JsonResponse
    {
        $request->validate([
            'number' => 'required|string',
            'message' => 'required|string',
            'media_url' => 'required|string',
            'filename' => 'nullable|string',
        ]);

        try {
            $response = $this->whatsAppService->sendMedia(
                $request->number,
                $request->message,
                $request->media_url,
                $request->filename
            );
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getGroups(): JsonResponse
    {
        try {
            $response = $this->whatsAppService->getGroups();
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendGroupTextMessage(Request $request): JsonResponse
    {
        $request->validate([
            'group_id' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $response = $this->whatsAppService->sendGroupTextMessage($request->group_id, $request->message);
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^01[0-2,5][0-9]{8}$/',
        ]);

        $otp = $this->whatsAppService->generateOtp();

        $expiresAt = now()->addMinutes(5);

        DB::table('users')->where('phone', $request->phone)->update([
            'otp' => $otp,
            'otp_sent_at' => now(),
            'otp_expires_at' => $expiresAt,
            'otp_attempts' => 0,
            'otp_verified_at' => null,
        ]);

        try {
            $message = "Your OTP is: {$otp}";
            $response = $this->whatsAppService->sendText($request->phone, $message);

            return response()->json(['message' => 'OTP sent successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^01[0-2,5][0-9]{8}$/',
            'otp' => 'required|string',
        ]);

        $user = DB::table('users')->where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($user->otp_expires_at && now()->gt($user->otp_expires_at)) {
            return response()->json(['error' => 'OTP has expired'], 400);
        }

        if ($user->otp != $request->otp) {
            DB::table('users')->where('phone', $request->phone)->increment('otp_attempts');

            if ($user->otp_attempts >= 3) {
                return response()->json(['error' => 'Too many OTP attempts'], 400);
            }

            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        DB::table('users')->where('phone', $request->phone)->update([
            'otp_verified_at' => now(),
        ]);

        return response()->json(['message' => 'OTP verified successfully!'], 200);
    }
}
