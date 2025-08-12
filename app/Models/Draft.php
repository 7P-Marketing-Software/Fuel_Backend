<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\DocumentModel;
use MongoDB\Laravel\Eloquent\Model as Eloquent;

class Draft extends Eloquent
{
    use DocumentModel;

    protected $connection = 'mongodb';
    protected $collection = 'drafts';

    protected $fillable = [
        'data',
        'type',
    ];
}
