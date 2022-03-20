<?php

namespace App\Http\Controllers;

use App\Http\Resources\StoreProductsResource;
use Illuminate\Http\Request;
use App\Models\StoreProduct;

class ProductsController extends Controller
{
    public $storeId;

    private $per_page;

    private $page;

    private $sort;

    public function __construct(Request $request)
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;

        /* These request parameters are common to all requests so gather
        them here in the constructor and assign them as class properties  */
        $this->per_page = $request->get('per_page');
        $this->page = $request->get('page');
        $this->sort = $request->get('sort');
        $this->preview_mode = session()->has('preview_mode');
    }

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

    /**
     * Products Controller - Lists all products (by optional section)
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke($section = null)
    {
        if(!$this->storeExists($this->storeId)) {
            abort('404', 'The specified store cannot be found!');
        }

        // First validate and set request values
        $perPage = $this->determinePerPage($this->per_page);
        $page = $this->determinePage($this->page);
        [$sort_field, $sort_direction] = $this->determineSort($this->sort);

        // Determine geographic location
        $geocode = $this->getGeocode()['country'];

        // Start query (eager load artist to prevent any n+1)
        $query = StoreProduct::with(['artist']);

        // Constrain by store
        $query->forStore($this->storeId);

        // Constrain by section (id or description)
        $query->inSection($section);

        // Only available (available, not deleted)
        $query->available();

        // Products with future launch date should not be shown (unless in preview mode)
        if(! $this->preview_mode) {
            $query->launched();
        }

        // Products with remove_date in the past should not be shown
        $query->notRemoved();

        // Products disabled by geocode filtered out
        $query->excludeCountries($geocode);

        // Apply sorting constraints
        $query->orderBy($sort_field, $sort_direction);

        // Paginate response
        $products = $query->simplePaginate(perPage: $perPage, page: $page);

        // Output paginated results through resource collection
        return StoreProductsResource::collection($products);
    }

}
