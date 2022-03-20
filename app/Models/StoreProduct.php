<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreProduct extends Model
{
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

    /**
     * Scope to exclude products that are disabled in a specified country
     *
     * @param $query
     * @param  string  $geocode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExcludeCountries($query, string $geocode): Builder
    {
        return $query->where("disabled_countries", "NOT LIKE", "%".$geocode."%");
    }

    /**
     * Scope to return products which have been launched
     *
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLaunched($query): Builder
    {
        return $query->where(function($query) {
                $query->where('launch_date', '=', '0000-00-00 00:00:00');
                $query->orWhere('launch_date', '<', now());
            });
    }

    /**
     * Scope to return products belonging to specified store
     *
     * @param $query
     * @param $storeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForStore($query, $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope to return products that are available and not marked as deleted
     *
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query): Builder
    {
        return $query->where('available', true)
            ->where('deleted', false);
    }

    /**
     * Scope to return products without removal dates specified or in the past
     *
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotRemoved($query): Builder
    {
        return $query->where(function($query) {
            $query->where('remove_date', '=', '0000-00-00 00:00:00');
            $query->orWhere('remove_date', '>', now());
        });
    }

    /**
     * Scope to return products belonging to specified section
     * Flexibly accepts description or id as parameter
     *
     * @param $query
     * @param  string|null  $section
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInSection($query, ?string $section)
    {
        if(! $section) {
            return $query;
        }

        return $query->whereHas('sections', function ($query) use ($section) {
            return $query->where(function($query) use ($section) {
                return $query->where("sections.description", "LIKE", $section)
                             ->orWhere('sections.id', '=', $section);
            });
        });
    }


}
