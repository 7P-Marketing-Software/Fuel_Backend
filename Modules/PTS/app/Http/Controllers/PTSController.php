<?php

namespace Modules\PTS\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PTS\Models\PTSLog;
use Modules\PTS\Models\PTSMeasurement;
use Modules\PTS\Models\WebSocketSession;
use App\Http\Controllers\Controller;

class PTSController extends Controller
{
    /**
     * Handle PTS-2 POST messages
     */
    public function handlePost(Request $request)
    {
        $data = $request->json()->all();

        $log = PTSLog::create([
            'pts_id' => $request->header('X-Pts-Id'),
            'method' => 'POST',
            'uri' => '/jsonPTS',
            'headers' => $request->headers->all(),
            'body' => $data,
            'ip_address' => $request->ip(),
            'firmware_version' => $request->header('X-Pts-Firmware-Version-DateTime'),
            'config_identifier' => $request->header('X-Pts-Configuration-Identifier'),
            'packet_type' => $data['Packets'][0]['Type'] ?? null,
            'packet_id' => $data['Packets'][0]['Id'] ?? null,
        ]);

        return response()->json([
            "Protocol" => "jsonPTS",
            "Packets" => [[
                "Id" => $data['Packets'][0]['Id'] ?? 0,
                "Type" => $data['Packets'][0]['Type'] ?? null,
                "Error" => false,
                "Message" => "OK"
            ]]
        ]);
    }

    /**
     * Handle WebSocket handshake attempts via HTTP
     */
    public function handleWebSocketHandshake(Request $request)
    {
        return response()->json([
            'error' => 'Use WebSocket connection on ws://127.0.0.1:8081',
            'websocket_url' => 'ws://127.0.0.1:8081'
        ], 426);
    }

    /**
     * Get PTS logs
     */
    public function getLogs(Request $request)
    {
        $logs = PTSLog::orderBy('created_at', 'desc')->paginate(20);

        return response()->json($logs);
    }

    public function getSessions(Request $request)
    {
        $sessions = WebSocketSession::orderBy('connected_at', 'desc')->paginate(20);

        return response()->json($sessions);
    }

    /**
     * Get measurements
     */
    public function getMeasurements(Request $request)
    {
        $measurements = PTSMeasurement::orderBy('measured_at', 'desc')->paginate();

        return response()->json($measurements);
    }
}
