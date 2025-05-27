<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
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

    public function getTotalAttribute()
    {
        return $this->price + $this->shipping;
    }
}
