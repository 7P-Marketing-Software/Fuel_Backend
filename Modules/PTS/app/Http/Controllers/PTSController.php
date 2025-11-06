<?php

namespace Modules\PTS\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\PTS\Models\Pts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PTSController extends Controller
{
    private $apiUrl = 'https://pioneerdynamic.com/show_pts.php';
    
    public function fetchAndStorePTSData()
    {
        try {
            $response = Http::get($this->apiUrl);
            
            if (!$response->successful()) {
                return $this->respondNotFound(null,'Failed to fetch data from external API');
            }
            
            $requestsData = $this->parseHTMLResponse($response->body());
            
            if (empty($requestsData)) {
                return $this->respondNotFound(null,'No data found or unable to parse response');
            }
            
            $storedCount = 0;
            $skippedCount = 0;
            
            foreach ($requestsData as $requestData) {
                $exists = Pts::where('request_time', $requestData['time'])
                            ->where('pts_id', $requestData['body']['PtsId'] ?? null)
                            ->exists();
                
                if (!$exists) {
                    $this->storePTSRequest($requestData);
                    $storedCount++;
                } else {
                    $skippedCount++;
                }
            }

            return $this->respondOk([
                'stored' => $storedCount,
                'skipped' => $skippedCount,
                'total_parsed' => count($requestsData)
            ], 'PTS data fetched and stored successfully');
                        
        } catch (\Exception $e) {
            Log::error('Error fetching PTS data: ' . $e->getMessage());
            return $this->respondNotFound(null,'Error processing request: ' . $e->getMessage());
        }
    }
    
    /**
     * Parse HTML response - SIMPLE AND DIRECT APPROACH
     */
    private function parseHTMLResponse($responseBody)
    {
        $requests = [];
        
        preg_match_all('/\{[^{}]*"Protocol"[^{}]*"jsonPTS"[^{}]*\}/', $responseBody, $jsonMatches);
        
        preg_match_all('/\{(?:[^{}]|(?R))*\}/', $responseBody, $broadMatches);
        
        $allJsonStrings = array_merge($jsonMatches[0], $broadMatches[0]);
        
        foreach ($allJsonStrings as $jsonString) {
            $body = $this->decodeJsonString($jsonString);
            if ($body && isset($body['Protocol']) && $body['Protocol'] === 'jsonPTS') {
                $requestData = $this->findRequestDataForJson($responseBody, $body, $jsonString);
                if ($requestData) {
                    $requests[] = $requestData;
                }
            }
        }
        
        return $requests;
    }
    
    /**
     * Find the request metadata for a JSON body
     */
    private function findRequestDataForJson($html, $body, $jsonString)
    {
        $requestData = [
            'body' => $body,
            'time' => null,
            'method' => null,
            'uri' => null,
        ];
        
        $jsonPos = strpos($html, $jsonString);
        if ($jsonPos === false) return $requestData;
        
        $start = max(0, $jsonPos - 2000);
        $section = substr($html, $start, 4000);
        
        if (preg_match('/Time:<\/strong>\s*([^<]+)/', $section, $matches)) {
            $requestData['time'] = trim($matches[1]);
        }
        
        if (preg_match('/Method:<\/strong>\s*([^<]+)/', $section, $matches)) {
            $requestData['method'] = trim($matches[1]);
        }
        
        if (preg_match('/URI:<\/strong>\s*([^<]+)/', $section, $matches)) {
            $requestData['uri'] = trim($matches[1]);
        }
        
        return $requestData;
    }
    
    
    /**
     * Decode JSON string with robust HTML entity handling
     */
    private function decodeJsonString($jsonString)
    {
        $jsonString = html_entity_decode($jsonString, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $jsonString = str_replace(["\n", "\r", "\t"], '', $jsonString);
        $jsonString = preg_replace('/\s+/', ' ', $jsonString);
        
        $body = json_decode($jsonString, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $body;
        }
        
        $jsonString = $this->fixJsonIssues($jsonString);
        $body = json_decode($jsonString, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $body;
        }
        
        Log::warning('JSON decode failed: ' . json_last_error_msg() . ' - String: ' . substr($jsonString, 0, 200));
        return null;
    }
    
    /**
     * Fix common JSON issues
     */
    private function fixJsonIssues($jsonString)
    {
        $jsonString = preg_replace('/([^\\\])"/', '$1\"', $jsonString);
        
        $jsonString = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)(\s*:)/', '$1"$2"$3', $jsonString);
        
        return $jsonString;
    }
    
    private function storePTSRequest($requestData)
    {
        try {
            $pts = new Pts();
            
            $pts->protocol = $requestData['body']['Protocol'] ?? null;
            $pts->pts_id = $requestData['body']['PtsId'] ?? null;
            $pts->packets = $requestData['body']['Packets'] ?? [];
            $pts->request_time = $requestData['time'] ?? null;
            $pts->method = $requestData['method'] ?? null;
            $pts->uri = $requestData['uri'] ?? null;
            
            $pts->save();
            
            Log::info('Stored PTS request - PTS ID: ' . ($pts->pts_id ?? 'Unknown') . ', Time: ' . ($pts->request_time ?? 'Unknown'));
            
            return $pts;
            
        } catch (\Exception $e) {
            Log::error('Error storing PTS request: ' . $e->getMessage());
            Log::error('Request data: ' . json_encode($requestData));
            throw $e;
        }
    }
    
    public function getStoredPTSData(Request $request)
    {
        $query = Pts::query();
        
        if ($request->has('pts_id')) {
            $query->where('pts_id', $request->pts_id);
        }
    
        if ($request->has('start_date')) {
            $query->where('request_time', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('request_time', '<=', $request->end_date);
        }
        
        $page = $request->get('page', 1);
        $data = $query->orderBy('request_time', 'desc')->paginate(null, ['*'], 'page', $page)->withPath($request->url());

        return $this->respondOk($data, 'PTS data retrieved successfully');
    }
}