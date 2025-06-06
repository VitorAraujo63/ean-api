<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'discount' => $this->discount,
            'notes' => $this->notes,
            'formatted' => [
                'unit_price' => 'R$ ' . number_format($this->unit_price, 2, ',', '.'),
                'total_price' => 'R$ ' . number_format($this->total_price, 2, ',', '.'),
                'discount' => 'R$ ' . number_format($this->discount, 2, ',', '.')
            ]
        ];
    }
}
