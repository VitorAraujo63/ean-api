<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'discount',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount' => 'decimal:2'
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Automatically calculate total_price when saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($saleItem) {
            $saleItem->total_price = ($saleItem->quantity * $saleItem->unit_price) - $saleItem->discount;
        });
    }

    // Accessors
    public function getFormattedUnitPriceAttribute()
    {
        return 'R$ ' . number_format($this->unit_price, 2, ',', '.');
    }

    public function getFormattedTotalPriceAttribute()
    {
        return 'R$ ' . number_format($this->total_price, 2, ',', '.');
    }

    public function getFormattedDiscountAttribute()
    {
        return 'R$ ' . number_format($this->discount, 2, ',', '.');
    }
}
