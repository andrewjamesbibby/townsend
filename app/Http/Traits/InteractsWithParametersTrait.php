<?php

namespace App\Http\Traits;

/**
 * InteractsWithParametersTrait
 *
 * This trait is used for interacting and validating client request parameters.
 * Params such as per_page, page and sort are likely to occur in more than
 * place, so it makes sense to store them all in one reusable location.
 */
trait InteractsWithParametersTrait
{
    /**
     * Check to ensure the storeID is not empty
     * (In reality this would likely be route/model bound or check the DB)
     *
     * @param $storeId
     * @return bool
     */
    public function storeExists($storeId): bool
    {
        return $storeId !== '';
    }

    /**
     * Return preview_mode status
     *
     * @return bool
     */
    public function isPreviewMode(): bool
    {
        return session()->has('preview_mode');
    }

    /**
     * Validates and sets the per_page parameter
     * An optional $default fallback can be specified
     *
     * @param $perPage
     * @param  int  $default
     * @return int
     */
    public function determinePerPage($perPage, int $default = 8): int
    {
        return !is_numeric($perPage) || $perPage < 1 ? $default : $perPage;
    }

    /**
     * Validates and sets the page parameter
     * An optional $default fallback can be specified
     *
     * @param $page
     * @param  int  $default
     * @return int
     */
    public function determinePage($page, int $default = 1): int
    {
        return !is_numeric($page) || $page < 1 ? $default : $page;
    }

    /**
     * Determines sort column and direction from given sort string
     *
     * @param $sort
     * @return string[]|null
     */
    public function determineSort($sort = null): ?array
    {
        return match (strtolower($sort)) {
            "az"     => ["name", "Asc"],
            "za"     => ["name", "Desc"],
            "low"    => ["price", "Asc"],
            "high"   => ["price", "Desc"],
            "old"    => ["release_date", "Asc"],
            "new"    => ["release_date", "Desc"],
            default => ["name", "Asc"],
        };
    }

    /**
     * Get Geo Code - For test purposes
     *
     * @return array
     */
    public function getGeocode(): array
    {
        return ['country' => 'GB'];
    }
}
