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
        'category_id',
        'price',
        'currency_id',
        'condition',
        'listing_type_id',
        'permalink',
        'thumbnail',
        'seller_id',
        'status',
        'raw_payload',
        'processed_at',
        'failed_reason',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
