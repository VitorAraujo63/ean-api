<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_number' => $this->sale_number,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'shipping' => $this->shipping,
            'total' => $this->total,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'sale_date' => $this->sale_date?->format('d/m/Y'),
            'notes' => $this->notes,
            'items_count' => $this->items->sum('quantity') ?? 0,
            'products_count' => $this->items->count() ?? 0,
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'formatted' => [
                'subtotal' => 'R$ ' . number_format($this->subtotal, 2, ',', '.'),
                'total' => 'R$ ' . number_format($this->total, 2, ',', '.'),
                'shipping' => 'R$ ' . number_format($this->shipping, 2, ',', '.')
            ],
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i')
        ];
    }
}
