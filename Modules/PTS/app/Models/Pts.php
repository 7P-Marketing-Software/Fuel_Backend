<?php

namespace Modules\PTS\Models;

use MongoDB\Laravel\Eloquent\Model;

class Pts extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'pts';
    
    protected $fillable = [
        'protocol',
        'pts_id',
        'packets',
        'packet_id',
        'packet_type',
        'data_time',
        'request_time',
        'method',
        'uri',
    ];
}