<?php

namespace Modules\PTS\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PTSController extends Controller
{
    public function handleNativePTS(Request $request)
    {
        $method = $request->method();
        $uri = $request->getRequestUri();
        
        $headers = $request->headers->all();

        if (($uri === '/api/jsonPTS' || $uri === '/jsonPTS') && $method === 'GET') {
            $entry = [
                'time' => now()->toDateTimeString(),
                'method' => $method,
                'uri' => $uri,
                'headers' => $headers,
                'body' => null
            ];

            $this->saveLogEntry($entry);

            // Quick HTTP response for GET requests
            return response()->json([
                'success' => true,
                'message' => 'PTS-2 API Endpoint',
                'usage' => 'Send POST requests with PTS-2 JSON format',
                'web_socket' => 'For real-time communication, connect to: ws://127.0.0.1:8081'
            ]);
        }

        if ($method === 'POST' && ($uri === '/api/jsonPTS' || $uri === '/jsonPTS')) {
            $rawBody = $request->getContent();
            $bodyJson = json_decode($rawBody, true);
            $loggedBody = ($bodyJson !== null) ? $bodyJson : $rawBody;

            $entry = [
                'time' => now()->toDateTimeString(),
                'method' => $method,
                'uri' => $uri,
                'headers' => $headers,
                'body' => $loggedBody
            ];

            $this->saveLogEntry($entry);

            $id = $bodyJson['Packets'][0]['Id'] ?? 0;
            $type = $bodyJson['Packets'][0]['Type'] ?? null;

            $response = [
                "Protocol" => "jsonPTS",
                "Packets" => [[
                    "Id" => $id,
                    "Type" => $type,
                    "Error" => false,
                    "Message" => "OK"
                ]]
            ];

            $responseBody = json_encode($response);

            return response($responseBody, 200)->withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'Connection' => 'close',
                'Content-Length' => strlen($responseBody)
            ]);
        }

        return response('Not Found', 404);
    }

    public function viewLogs()
    {
        $logFile = storage_path('logs/pts2_log.txt');

        if (!file_exists($logFile)) {
            return $this->respondNotFound(null,"No requests logged yet.");
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $allEntries = array_map(function($line) {
            return json_decode($line, true);
        }, $lines);

        $allEntries = array_reverse($allEntries);

        return $this->respondOk([
            'total_entries' => count($allEntries),
            'entries' => $allEntries
        ]);
    }

    private function saveLogEntry(array $entry)
    {
        $logFile = storage_path('logs/pts2_log.txt');

        $lines = file_exists($logFile) ?
            file($logFile, FILE_IGNORE_NEW_LINES) : [];

        $lines[] = json_encode($entry, JSON_UNESCAPED_SLASHES);

        if (count($lines) > 10) {
            $lines = array_slice($lines, -10);
        }

        file_put_contents($logFile, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
