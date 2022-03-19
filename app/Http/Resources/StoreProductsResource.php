<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreProductsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "image"        => $this->getImageUrl(),
            "id"           => $this->id,
            "artist"       => $this->artist->name,
            "title"        => $this->getDisplayName(),
            "description"  => $this->description,
            "price"        => $this->getPriceInCurrency(),
            "format"       => $this->type,
            "release_date" => $this->release_date,
        ];
    }
}
