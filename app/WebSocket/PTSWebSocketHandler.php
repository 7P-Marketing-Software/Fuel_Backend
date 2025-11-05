<?php

namespace App\WebSocket;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;
use Modules\PTS\Models\PTSLog;
use Modules\PTS\Models\WebSocketSession;
use Modules\PTS\Services\PTSDataProcessor;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PTSWebSocketHandler
{
    protected $worker;
    protected $dataProcessor;
    protected $sessions = [];

    public function __construct()
    {
        $this->initializeWorker();
    }

    private function initializeWorker()
    {
        $this->worker = new Worker("websocket://0.0.0.0:8081");

        $this->worker->count = 1; // Single process for PTS
        $this->worker->name = 'PTS-2 WebSocket Server';

        // Set event handlers
        $this->worker->onConnect = [$this, 'onConnect'];
        $this->worker->onMessage = [$this, 'onMessage'];
        $this->worker->onClose = [$this, 'onClose'];
        $this->worker->onError = [$this, 'onError'];

        echo "🚀 PTS-2 WebSocket Server Started\n";
        echo "📍 Endpoint: ws://0.0.0.0:8081\n";
        echo "📡 Protocol: PTS-2 JSON (Native)\n";
        echo "⏰ " . Carbon::now()->toDateTimeString() . "\n";
        echo str_repeat("═", 60) . "\n";
    }

    public function onConnect(TcpConnection $connection)
    {
        $connection->id = (string) $connection->id;

        echo "✅ NEW CONNECTION:\n";
        echo "   Session: {$connection->id}\n";
        echo "   IP: {$connection->getRemoteIp()}\n";
        echo "   Port: {$connection->getRemotePort()}\n";

        // Initialize session
        $this->sessions[$connection->id] = [
            'connected_at' => Carbon::now(),
            'pts_id' => 'unknown',
            'headers' => []
        ];

        Log::channel('pts')->info('PTS WebSocket connected', [
            'session_id' => $connection->id,
            'ip' => $connection->getRemoteIp()
        ]);
    }

    public function onMessage(TcpConnection $connection, $data)
    {
        $session = $this->sessions[$connection->id] ?? [];

        echo "📨 MESSAGE RECEIVED:\n";
        echo "   Session: {$connection->id}\n";
        echo "   Length: " . strlen($data) . " bytes\n";
        echo "   Content: {$data}\n";

        try {
            $messageData = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }

            // Validate PTS-2 protocol
            if (!isset($messageData['Protocol']) || $messageData['Protocol'] !== 'jsonPTS') {
                throw new \Exception('Invalid PTS protocol');
            }

            echo "   ✅ Valid PTS-2 protocol\n";

            // Process the PTS message
            $this->processPTSMessage($messageData, $connection);

        } catch (\Exception $e) {
            echo "   ❌ Error: " . $e->getMessage() . "\n";
            $this->sendError($connection, $e->getMessage());

            Log::channel('pts')->error('WebSocket message error', [
                'session_id' => $connection->id,
                'error' => $e->getMessage()
            ]);
        }

        echo "---\n";
    }

    public function onClose(TcpConnection $connection)
    {
        $session = $this->sessions[$connection->id] ?? [];
        $ptsId = $session['pts_id'] ?? 'unknown';
        $duration = isset($session['connected_at']) ?
            $session['connected_at']->diffInSeconds(Carbon::now()) : 0;

        echo "❌ CONNECTION CLOSED:\n";
        echo "   Session: {$connection->id}\n";
        echo "   PTS ID: {$ptsId}\n";
        echo "   Duration: {$duration}s\n";
        echo "---\n";

        // Store session in database if we have PTS ID
       
        unset($this->sessions[$connection->id]);

        Log::channel('pts')->info('PTS WebSocket disconnected', [
            'session_id' => $connection->id,
            'pts_id' => $ptsId,
            'duration' => $duration
        ]);
    }

    public function onError(TcpConnection $connection, $code, $msg)
    {
        echo "💥 WEBSOCKET ERROR:\n";
        echo "   Session: {$connection->id}\n";
        echo "   Code: {$code}\n";
        echo "   Message: {$msg}\n";
        echo "---\n";

        Log::channel('pts')->error('WebSocket error', [
            'session_id' => $connection->id,
            'code' => $code,
            'message' => $msg
        ]);
    }

    private function processPTSMessage(array $data, TcpConnection $connection)
    {
        $packetType = $data['Packets'][0]['Type'] ?? 'unknown';
        $packetId = $data['Packets'][0]['Id'] ?? 'unknown';
        $ptsId = $data['PtsId'] ?? 'unknown';

        echo "   📦 Packet Type: {$packetType}\n";
        echo "   🔢 Packet ID: {$packetId}\n";
        echo "   🆔 PTS ID: {$ptsId}\n";

        // Update session with PTS ID
        if (isset($this->sessions[$connection->id])) {
            $this->sessions[$connection->id]['pts_id'] = $ptsId;
            $this->sessions[$connection->id]['messages_received'] =
                ($this->sessions[$connection->id]['messages_received'] ?? 0) + 1;
        }
        
        // Process data (store measurements, etc.)
        $this->dataProcessor->processMessage($data, $ptsId);

        // Send confirmation response
        $this->sendConfirmation($connection, $data);

        echo "   ✅ Processed & confirmation sent\n";

        Log::channel('pts')->info('PTS message processed', [
            'session_id' => $connection->id,
            'pts_id' => $ptsId,
            'packet_type' => $packetType,
            'packet_id' => $packetId
        ]);
    }

    private function sendConfirmation(TcpConnection $connection, array $originalData)
    {
        $id = $originalData['Packets'][0]['Id'] ?? 0;
        $type = $originalData['Packets'][0]['Type'] ?? null;

        $response = [
            "Protocol" => "jsonPTS",
            "Packets" => [[
                "Id" => $id,
                "Type" => $type,
                "Error" => false,
                "Message" => "OK"
            ]]
        ];

        $connection->send(json_encode($response));
    }

    private function sendError(TcpConnection $connection, string $message)
    {
        $response = [
            "Protocol" => "jsonPTS",
            "Packets" => [[
                "Id" => 0,
                "Type" => "Error",
                "Error" => true,
                "Message" => $message
            ]]
        ];

        $connection->send(json_encode($response));
    }

    public function run()
    {
        Worker::runAll();
    }
}
