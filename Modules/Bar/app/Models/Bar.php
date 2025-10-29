<?php

namespace Modules\Bar\Models;

use App\Http\Traits\ArchiveTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bar extends Model
{
    use HasFactory, SoftDeletes, ArchiveTrait;

    protected $fillable = [
        'title',
        'description',
        'image',
        'link'
    ];
}
