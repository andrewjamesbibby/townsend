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
    protected function storeExists($storeId): bool
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
     * Gets 'per_page' parameter from request
     * An optional $default fallback can be specified
     *
     * @param  int  $default
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @return int
     */
    protected function getPerPage(int $default = 8): int
    {
        $perPage = request()->get('per_page', $default);

        return !is_numeric($perPage) || $perPage < 1 ? $default : $perPage;
    }

    /**
     * Gets 'page' parameter from request
     * An optional $default fallback can be specified
     *
     * @param  int  $default
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @return int
     */
    protected function getPage(int $default = 1): int
    {
        $page = request()->get('page', $default);

        return !is_numeric($page) || $page < 1 ? $default : $page;
    }

    /**
     * Get 'sort' parameter from request
     * Determines sort column and direction from given sort string
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @return string[]|null
     */
    protected function getSort(): ?array
    {
        $sort = request()->get('sort');

        return match (strtolower($sort)) {
            "az"     => ["name", "Asc"],
            "za"     => ["name", "Desc"],
            "low"    => ["price", "Asc"],
            "high"   => ["price", "Desc"],
            "old"    => ["release_date", "Asc"],
            "new"    => ["release_date", "Desc"],
            default => null,
        };
    }

    /**
     * Get Geo Code - For test purposes
     *
     * @return array
     */
    protected function getGeocode(): array
    {
        return ['country' => 'GB'];
    }
}
