<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class BookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $purchased = in_array($this->id, $request->attributes->get('purchased_book_ids', []), true);
        $inStock = $this->stock === null || (int) $this->stock > 0;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->when($request->routeIs('api.books.show'), $this->description),
            'thumbnail_url' => $this->thumbnail_url,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'effective_price' => $this->effective_price,
            'stock' => $this->stock !== null ? (int) $this->stock : null,
            'in_stock' => $inStock,
            'purchased' => $purchased,
        ];
    }
}
