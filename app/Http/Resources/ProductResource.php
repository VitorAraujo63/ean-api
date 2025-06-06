<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ean' => $this->ean,
            'description' => $this->description,
            'brand' => $this->brand,
            'price' => $this->price,
            'cost' => $this->cost,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'ncm' => $this->ncm,
            'unit' => $this->unit,
            'gross_weight' => $this->gross_weight,
            'net_weight' => $this->net_weight,
            'image' => $this->image,
            'source' => $this->source,
            'complete' => $this->complete,
            'formatted' => [
                'price' => 'R$ ' . number_format($this->price, 2, ',', '.'),
                'cost' => 'R$ ' . number_format($this->cost, 2, ',', '.'),
                'gross_weight' => number_format($this->gross_weight, 3, ',', '.') . ' kg',
                'net_weight' => number_format($this->net_weight, 3, ',', '.') . ' kg'
            ],
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i')
        ];
    }
}
