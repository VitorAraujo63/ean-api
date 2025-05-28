<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'product_id', // Add this line
        'price',
        'shipping',
        'status',
        'payment_method',
        'sale_date'
    ];

    protected $casts = [
        'sale_date' => 'date',
        'price' => 'decimal:2',
        'shipping' => 'decimal:2'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Add this relationship
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalAttribute()
    {
        return $this->price + $this->shipping;
    }
}
