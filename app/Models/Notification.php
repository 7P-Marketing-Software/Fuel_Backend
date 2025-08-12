<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;

class Notification extends Model
{
    //
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'title',
        'image',
        'body',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    public function sender()
    {
        return $this->belongTo(User::class, 'sender_id');
    }
}
