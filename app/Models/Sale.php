<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'shipping',
        'status',
        'payment_method',
        'sale_date',
        'notes',
        'sale_number'
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping' => 'decimal:2'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'sale_items')
                    ->withPivot(['quantity', 'unit_price', 'total_price', 'discount', 'notes'])
                    ->withTimestamps();
    }

    // Auto-generate sale number with better logic
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = static::generateSaleNumber();
            }
        });
    }

    // Generate unique sale number
    public static function generateSaleNumber()
    {
        $year = date('Y');
        $prefix = "VND-{$year}-";

        // Get the last sale number for this year
        $lastSale = static::where('sale_number', 'like', $prefix . '%')
                          ->orderBy('sale_number', 'desc')
                          ->first();

        if ($lastSale) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastSale->sale_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            // First sale of the year
            $nextNumber = 1;
        }

        // Format with leading zeros
        $formattedNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Check if this number already exists (safety check)
        $saleNumber = $prefix . $formattedNumber;
        while (static::where('sale_number', $saleNumber)->exists()) {
            $nextNumber++;
            $formattedNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            $saleNumber = $prefix . $formattedNumber;
        }

        return $saleNumber;
    }

    // Calculate totals from items
    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('total_price');
        $this->total = $this->subtotal - $this->discount_total + $this->tax_total + $this->shipping;
        $this->save();
    }

    // Accessors
    public function getFormattedSubtotalAttribute()
    {
        return 'R$ ' . number_format($this->subtotal, 2, ',', '.');
    }

    public function getFormattedTotalAttribute()
    {
        return 'R$ ' . number_format($this->total, 2, ',', '.');
    }

    public function getFormattedShippingAttribute()
    {
        return 'R$ ' . number_format($this->shipping, 2, ',', '.');
    }

    public function getItemsCountAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getProductsCountAttribute()
    {
        return $this->items->count();
    }
}
