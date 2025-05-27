<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'ean',
        'description',
        'brand',
        'ncm',
        'unit',
        'gross_weight',
        'net_weight',
        'image',
        'source',
        'complete',
        'price',
        'cost',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'cost' => 'decimal:3',
        'gross_weight' => 'decimal:3',
        'net_weight' => 'decimal:3',
        'complete' => 'boolean',
    ];
}
