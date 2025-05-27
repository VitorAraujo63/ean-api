<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ean' => $this->ean,
            'name' => $this->description,
            'brand' => $this->brand,
            'unit' => $this->unit,
            'ncm' => $this->ncm,
            'weight' => [
                'gross' => $this->gross_weight,
                'net' => $this->net_weight,
            ],
            'image' => $this->image,
            'source' => $this->source,
            'type' => $this->type,
            'complete' => $this->complete,
            'price' =>$this->price,
            'cost' =>$this->cost,
        ];
    }
}
