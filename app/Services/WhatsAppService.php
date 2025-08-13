<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected $baseUrl;
    protected $accessToken;
    protected $instanceId;

    public function __construct()
    {
        $this->baseUrl = env('WHATSAPP_BASE_URL');
        $this->accessToken = env('WHATSAPP_ACCESS_TOKEN');
        $this->instanceId = env('WHATSAPP_INSTANCE_ID');

        if (!$this->baseUrl || !$this->accessToken || !$this->instanceId) {
        }
    }


    public function sendText($number, string $message)
    {
        $number = (int)$number;
        $response = Http::post("https://wats-bot.com/api/send", [
            'number' => $number,
            'type' => 'text',
            'message' => $message,
            'access_token' => $this->accessToken,
            'instance_id' => $this->instanceId,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new Exception("Failed to Send Text WhatsApp: " . $response->body());
    }


}
