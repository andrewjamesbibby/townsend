<?php

namespace App\Http\Controllers;

use App\Http\Traits\InteractsWithParametersTrait;
use App\Http\Resources\StoreProductsResource;
use Illuminate\Http\Request;
use App\Models\StoreProduct;

class ProductsController extends Controller
{
    use InteractsWithParametersTrait;

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

        /* No point going any further if requested store is not available. I
        imagine in reality the storeBuilder would throw an exception or some
        middleware would handle this gracefully */
        if(!$this->storeExists($this->storeId)) {
            abort('404', 'The specified store cannot be found!');
        }

        /* These request parameters are common to all requests so gather
        them here in the constructor and assign them as class properties  */
        $this->per_page = $request->get('per_page');
        $this->page = $request->get('page');
        $this->sort = $request->get('sort');
    }

    /**
     * Products Controller - Lists all products (by optional section)
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke($section = null)
    {
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
        if(! $this->isPreviewMode()) {
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
