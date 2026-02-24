<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';

    protected $fillable = [
        'meli_id',
        'title',
        'status',
        'created',
        'updated',
        'processed_at',
    ];

    protected $casts = [
        'created' => 'datetime',
        'updated' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
