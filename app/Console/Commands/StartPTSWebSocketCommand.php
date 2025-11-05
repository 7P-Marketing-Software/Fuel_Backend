<?php

namespace App\Console\Commands;

use App\WebSocket\PTSWebSocketHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StartPTSWebSocketCommand extends Command
{
    protected $signature = 'pts:websocket
                            {--port=8081 : Port to run the PTS WebSocket server}
                            {--host=0.0.0.0 : Host to bind the server}';
    
    protected $description = 'Start PTS-2 WebSocket server for fuel controllers';

    public function handle()
    {
        $port = $this->option('port');
        $host = $this->option('host');
        
        $this->info("🚀 Starting PTS-2 WebSocket Server...");
        $this->line("📍 Host: <comment>{$host}</comment>");
        $this->line("🔌 Port: <comment>{$port}</comment>");
        $this->line("📡 Endpoint: <comment>ws://{$host}:{$port}</comment>");
        $this->line("⏰ Started at: <comment>" . now()->toDateTimeString() . "</comment>");
        $this->line(str_repeat("─", 60));
        
        try {
            $handler = new PTSWebSocketHandler($host, $port);
            $handler->run();
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to start server: " . $e->getMessage());
            Log::channel('pts')->error('Failed to start WebSocket server', [
                'error' => $e->getMessage()
            ]);
            return 1;
        }

        return 0;
    }
}
