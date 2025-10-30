<?php

namespace Modules\PTS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PTSLog extends Model
{
    use HasFactory;

    protected $table = 'pts_logs';

    protected $fillable = [
        'pts_id', 'method', 'uri', 'headers', 'body', 'ip_address',
        'firmware_version', 'config_identifier', 'packet_type', 'packet_id',
        'forwarded', 'forward_response', 'error_message'
    ];

    protected $casts = [
        'headers' => 'array',
        'body' => 'array',
        'forwarded' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function measurements()
    {
        return $this->hasMany(PTSMeasurement::class, 'pts_id', 'pts_id');
    }

    public function websocketSessions()
    {
        return $this->hasMany(WebSocketSession::class, 'pts_id', 'pts_id');
    }

}
