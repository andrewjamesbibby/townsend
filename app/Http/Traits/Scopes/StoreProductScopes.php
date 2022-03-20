<?php

namespace App\Http\Traits\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * StoreProductScopes
 *
 * This is a 'single-use' Trait for StoreProduct Model's query scopes which
 * helps to keep the model organised and prevent Model bloating.
 */
trait StoreProductScopes
{
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
