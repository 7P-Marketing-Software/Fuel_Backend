<?php

namespace Modules\PTS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PTSMeasurement extends Model
{
    use HasFactory;

    protected $table = 'pts_measurements';

    protected $fillable = [
        'pts_id', 'probe_id', 'fuel_level', 'temperature_1',
        'temperature_2', 'additional_data', 'measured_at'
    ];

    protected $casts = [
        'additional_data' => 'array',
        'measured_at' => 'datetime',
        'fuel_level' => 'decimal:2',
        'temperature_1' => 'decimal:2',
        'temperature_2' => 'decimal:2',
    ];

    // Relationships
    public function ptsLog()
    {
        return $this->belongsTo(PTSLog::class, 'pts_id', 'pts_id');
    }

    // Scopes
    public function scopeByProbe($query, $probeId)
    {
        return $query->where('probe_id', $probeId);
    }

    public function scopeByPtsId($query, $ptsId)
    {
        return $query->where('pts_id', $ptsId);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('measured_at', '>=', now()->subHours($hours));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('measured_at', today());
    }

    public function scopeFuelLevelBelow($query, $level)
    {
        return $query->where('fuel_level', '<', $level);
    }

    public function scopeFuelLevelAbove($query, $level)
    {
        return $query->where('fuel_level', '>', $level);
    }
}
