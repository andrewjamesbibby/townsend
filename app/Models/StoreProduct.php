<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Http\Traits\Scopes\StoreProductScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreProduct extends Model
{
    use StoreProductScopes;
    use HasFactory;

    public const IMAGES_DOMAIN = "https://img.tmstor.es/";

    public $table = 'store_products';

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(
            Section::class,
            'store_products_section',
            'store_product_id',
            'section_id',
            'id',
            'id'
        )
            ->withPivot('position')
            ->orderBy('position', 'ASC');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'artist_id', 'id');
    }

    /**
     * Determine product image url
     * $length check defaults to 2 although is configurable
     *
     * @param  int  $length
     * @return string
     */
    public function getImageUrl(int $length = 2)
    {
        if(Str::length($this->image_format) > $length) {
            return self::IMAGES_DOMAIN . $this->id . $this->image_format;
        }

        return self::IMAGES_DOMAIN."noimage.jpg";
    }

    /**
     * Determine product's display name
     * $length check defaults to 3 although is configurable
     *
     * @param  int  $length
     * @return string
     */
    public function getDisplayName(int $length = 3)
    {
        return Str::length($this->display_name) > $length ? $this->display_name : $this->name;
    }

    /**
     * Get product price by currency code
     *
     * @param  string|null  $currency
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @return mixed
     */
    public function getPriceInCurrency(?string $currency = null): mixed
    {
        // Use specified currency or check session for value
        $currency = $currency ?? session()->get("currency", "GBP");

        // Build list of supported currencies
        $supported_currencies = ["USD", "EUR", "GBP"];

        // This allows lower/uppercase values without issue
        $currency = strtoupper($currency);

        // Match and return the appropriate product price
        return (float) match($currency) {
            "GBP"   => $this->price,
            "EUR"   => $this->euro_price,
            "USD"   => $this->dollar_price,
            default => $this->price
        };
    }
}
