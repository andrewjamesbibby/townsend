<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\store_products;

class LegacyProductsController extends Controller
{
    public int $storeId;

    public function __construct(Request $request)
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;

        /* Gather request parameters  */
        $this->number = $request->get('number');
        $this->page = $request->get('page');
        $this->sort = $request->get('sort', 0);
    }

    /**
     * List 'All' products
     *
     * @return array|false|void
     */
    public function index()
    {
        return (new store_products)->sectionProducts($this->storeId,'%',$this->number, $this->page, $this->sort);
    }

    /**
     * List products belonging to a specified section
     *
     * @param $section
     * @return array|false|void
     */
    public function show($section)
    {
        return (new store_products)->sectionProducts($this->storeId, $section, $this->number, $this->page, $this->sort);
    }
}
