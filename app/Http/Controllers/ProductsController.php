<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\store_products;

class ProductsController extends Controller
{
    public $storeId;

    public function __construct()
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;
    }

    public function index()
    {
        $products = (new store_products())->sectionProducts($this->storeId, '%', 10, 1,0);

        return $products;
    }

    public function show()
    {
        return 'show';
    }
}
