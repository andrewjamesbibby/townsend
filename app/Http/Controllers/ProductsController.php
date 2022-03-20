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
    }

    /**
     * Products Controller - Lists all products (by optional section)
     *
     * @param  null  $section
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke($section = null)
    {
        // Start query (eager load to prevent any n+1)
        $query = StoreProduct::with(['artist','sections']);

        // Constrain by store
        $query->forStore($this->storeId);

        // Constrain by section (id or description)
        $query->inSection($section);

        // Only available (available, not deleted)
        $query->available();

        // Products with future launch date should not be shown (unless in preview mode)
        if (! $this->isPreviewMode()) {
            $query->launched();
        }

        // Products with remove_date in the past should not be shown
        $query->notRemoved();

        // Products disabled by geocode filtered out
        $query->excludeCountries($this->getGeocode()['country']);

        // Determine field/column and apply sort
        [$sort_field, $sort_direction] = $this->getSort();
        $query->applySort($sort_field, $sort_direction);

        // Paginate response
        $products = $query->simplePaginate(
            perPage: $this->getPerPage(),
            page: $this->getPage()
        );

        // Output paginated results through resource collection
        return StoreProductsResource::collection($products);
    }
}
