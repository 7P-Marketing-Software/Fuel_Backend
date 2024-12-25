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
            throw new Exception("WhatsApp environment variables are not set correctly.");
        }
    }

    private function makeRequest(string $method, string $url, array $data = [])
    {
        $data['instance_id'] = $this->instanceId;
        $data['access_token'] = $this->accessToken;

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->$method("https://wats-bot.com/api/{$url}", $data);

        if ($response->successful()) {
            return $response->json();
        }

        throw new Exception("Request to {$url} failed: " . $response->body());
    }

    public function createInstance()
    {
        $response = Http::post("{$this->baseUrl}/create_instance", [
            'access_token' => $this->accessToken,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['instance_id'] ?? null;
        }

        throw new Exception("Failed to create WhatsApp instance: " . $response->body());
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

    public function getQRCode()
    {
        return $this->makeRequest('get', 'get_qrcode');
    }

    public function rebootInstance()
    {
        return $this->makeRequest('post', 'reboot');
    }

    public function resetInstance()
    {
        return $this->makeRequest('post', 'reset_instance');
    }

    public function reconnect()
    {
        return $this->makeRequest('post', 'reconnect');
    }

    public function setWebhook(string $webhookUrl, bool $enable)
    {
        return $this->makeRequest('post', 'set_webhook', [
            'webhook_url' => $webhookUrl,
            'enable' => $enable,
        ]);
    }

    public function sendTemplateMessage(string $number, string $type, string $template)
    {
        return $this->makeRequest('post', 'send', [
            'number' => $number,
            'type' => $type,
            'template' => $template,
        ]);
    }

    public function sendMedia(string $number, string $message, string $mediaUrl, ?string $filename = null)
    {
        return $this->makeRequest('post', 'send', array_filter([
            'number' => $number,
            'type' => 'media',
            'message' => $message,
            'media_url' => $mediaUrl,
            'filename' => $filename,
        ]));
    }

    public function getGroups()
    {
        return $this->makeRequest('post', 'get_groups');
    }

    public function sendGroupTextMessage(string $groupId, string $message)
    {
        return $this->makeRequest('post', 'send_group', [
            'group_id' => $groupId,
            'type' => 'text',
            'message' => $message,
        ]);
    }


    public function generateOtp(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
