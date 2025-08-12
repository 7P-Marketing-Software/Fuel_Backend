<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\DocumentModel;
use MongoDB\Laravel\Eloquent\Model as Eloquent;
class ActivityLog extends Eloquent
{
    use DocumentModel;

    protected $connection = 'mongodb';
    protected $collection = 'activity_logs';

    protected $fillable = [
        'user_id',
        'role',
        'logs'
    ];
}
