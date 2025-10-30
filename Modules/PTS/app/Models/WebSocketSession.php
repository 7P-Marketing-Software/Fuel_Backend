<?php

namespace Modules\PTS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebSocketSession extends Model
{
    use HasFactory;

    protected $table = 'websocket_sessions';

    protected $fillable = [
        'session_id', 'pts_id', 'remote_ip', 'handshake_headers',
        'connected_at', 'disconnected_at', 'messages_received',
        'messages_sent', 'connection_duration'
    ];

    protected $casts = [
        'handshake_headers' => 'array',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    // Relationships
    public function ptsLogs()
    {
        return $this->hasMany(PTSLog::class, 'pts_id', 'pts_id');
    }

    // Methods
    public function markAsDisconnected()
    {
        $this->update([
            'disconnected_at' => now(),
            'connection_duration' => $this->connection_duration
        ]);
    }

    public function incrementMessagesReceived($count = 1)
    {
        $this->increment('messages_received', $count);
    }

    public function incrementMessagesSent($count = 1)
    {
        $this->increment('messages_sent', $count);
    }
}
