<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Facades\Artisan;
use App\Models\StoreProduct;
use App\Models\Section;
use App\Models\Artist;
use Tests\TestCase;

class ProductsControllerTest extends TestCase
{
   use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate');

        // Create Artist (Used for all test StoreProducts)
        $this->artist = Artist::factory()->create();
    }

    /** @test */
    public function product_structure_is_as_expected()
    {
        $product = StoreProduct::factory()->create();

        $this->getJson('/products')
             ->assertJson(fn (AssertableJson $json) =>
                $json->has('meta')
                    ->has('links')
                    ->has('data', 1)
                    ->has('data.0', fn ($json) =>
                        $json->where('image', StoreProduct::IMAGES_DOMAIN . $product->id . $product->image_format)
                            ->where('id', $product->id)
                            ->where('id', $product->id)
                            ->where('artist', $this->artist->name)
                            ->where('title', $product->display_name)
                            ->where('description', $product->description)
                            ->where('price', $product->price)
                            ->where('format', $product->type)
                            ->where('release_date', $product->release_date)
                            ->etc()
                    )
            );
    }

    /** @test */
    public function product_title_shown_as_expected_when_display_name_is_short()
    {
        // Display name set short (2 chars)
        $product = StoreProduct::factory()->create(['display_name' => 'ab']);

        $this->getJson('/products')
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('meta')
                ->has('links')
                ->has('data', 1)
                ->has('data.0', fn ($json) =>
                    $json->where('title', $product->name)
                        ->etc()
                    )
            );
    }

    /** @test */
    public function product_default_image_is_used_when_image_format_invalid()
    {
        // Set an empty image format
        StoreProduct::factory()->create(['image_format' => '']);

        $this->getJson('/products')
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('meta')
                ->has('links')
                ->has('data', 1)
                ->has('data.0', fn ($json) =>
                    $json->where('image', StoreProduct::IMAGES_DOMAIN . "noimage.jpg")
                        ->etc()
                )
            );
    }

    /** @test */
    public function product_is_shown_in_expected_currencies()
    {
        // Create product with different prices for each currency
        StoreProduct::factory()->create([
            'price' => 1.99,
            'euro_price' => 2.99,
            'dollar_price' => 3.99
        ]);

        // Confirm default without session will be GBP price
        $response = $this->getJson('/products');
        $this->assertTrue($response["data"][0]["price"]== 1.99);

        // Update Currency via session and confirm GBP price
        $response = $this->withSession(["currency" => "GBO"])->getJson('/products');
        $this->assertTrue($response["data"][0]["price"]== 1.99);

        // Update Currency via session and confirm EUR price
        $response = $this->withSession(["currency" => "EUR"])->getJson('/products');
        $this->assertTrue($response["data"][0]["price"]== 2.99);

        // Update Currency via session and confirm USD price
        $response = $this->withSession(["currency" => "USD"])->getJson('/products');
        $this->assertTrue($response["data"][0]["price"]== 3.99);
    }

    /** @test */
    public function product_is_not_shown_if_disabled_by_country_code()
    {
        // Add 'GB' to the products disabled country list
        StoreProduct::factory()->disabled('GB')->create(['disabled_countries' => 'GB']);

        $this->getJson('/products')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 0)
                    ->etc()
            );
    }

    /** @test */
    public function product_is_not_shown_if_unavailable()
    {
        // Create an 'unavailable' product
        StoreProduct::factory()->unavailable()->create();

        $this->getJson('/products')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 0)
                    ->etc()
                );
    }

    /** @test */
    public function product_is_not_shown_if_deleted()
    {
        // Create an 'deleted' product
        StoreProduct::factory()->deleted()->create();

        $this->getJson('/products')
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('data', 0)
                ->etc()
            );
    }

    /** @test */
    public function products_with_future_launch_date_not_returned_unless_in_preview_mode()
    {
        // Create product with future launch date
        StoreProduct::factory()->create([
            "launch_date" => now()->addMonth()
        ]);

        // Confirm the future launch product is not visible
        $this->getJson('/products')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 0)
                    ->etc()
                );

        // Product is visible when in 'preview mode'
        $this->withSession(['preview_mode' => true])
            ->getJson('/products')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 1)
                    ->etc()
            );
    }

    /** @test */
    public function products_with_remove_dates_in_the_past_are_not_returned()
    {
        // Create product with removal date than is in the past
        StoreProduct::factory()->create([
            "remove_date" => now()->subMonth()
        ]);

        $this->getJson('/products')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 0)
                    ->etc()
            );
    }

    /** @test */
    public function can_limit_results_to_specified_number_of_records()
    {
        // Create 20 products
        StoreProduct::factory(20)->create();

        // Confirm per_page=5 returns only 5 products
        $this->getJson('/products?per_page=5')
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('data', 5)
                ->etc()
            );

        // Confirm per_page=-1 returns default 8 products
        $this->getJson('/products?per_page=-1')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 8)
                    ->etc()
            );

        // Confirm non-numeric per_page value returns default 8 products
        $this->getJson('/products?per_page=-1')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 8)
                    ->etc()
            );
    }

    /** @test */
    public function can_return_results_at_specified_page(){}

    /** @test */
    public function can_list_products_by_specified_section_id_or_description()
    {
        // Create products
        $product1 = StoreProduct::factory()->create();
        $product2 = StoreProduct::factory()->create();

        // Create section
        $section = Section::factory()->create(['description' => 'Tickets']);

        // Assign product1 to section
        $product1->sections()->attach($section->id);

        // Result should only return $product1 which belongs to 'Tickets' section
        $this->getJson("/products/{$section->description}")
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('meta')
                ->has('links')
                ->has('data', 1)
                ->has('data.0', fn ($json) =>
                    $json->where('image', StoreProduct::IMAGES_DOMAIN . $product1->id . $product1->image_format)
                        ->where('id', $product1->id)
                        ->where('artist', $this->artist->name)
                        ->where('title', $product1->display_name)
                        ->where('description', $product1->description)
                        ->where('price', $product1->price)
                        ->where('format', $product1->type)
                        ->where('release_date', $product1->release_date)
                        ->etc()
                )
            );

        // Repeated but make request with section ID
        $this->getJson("/products/{$section->id}")
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('meta')
                ->has('links')
                ->has('data', 1)
                ->has('data.0', fn ($json) =>
                $json->where('image', StoreProduct::IMAGES_DOMAIN . $product1->id . $product1->image_format)
                    ->where('id', $product1->id)
                    ->where('artist', $this->artist->name)
                    ->where('title', $product1->display_name)
                    ->where('description', $product1->description)
                    ->where('price', $product1->price)
                    ->where('format', $product1->type)
                    ->where('release_date', $product1->release_date)
                    ->etc()
                )
            );
    }

    /** @test */
    public function can_order_products_by_price_asc()
    {
        $cheap_product = StoreProduct::factory()->create(['price' => 1.99]);
        $average_product = StoreProduct::factory()->create(['price' => 12.99]);
        $expensive_product = StoreProduct::factory()->create(['price' => 89.99]);

        // Confirm can sort low to high (Asc)
        $this->getJson("/products?sort=low")
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('links')
                ->has('meta')
                ->has('data', 3)
                ->has('data.0', fn ($json) =>
                    $json->where('price', $cheap_product->price)->etc()
                )
                ->has('data.1', fn ($json) =>
                    $json->where('price', $average_product->price)->etc()
                )
                ->has('data.2', fn ($json) =>
                    $json->where('price', $expensive_product->price)->etc()
                )
            );
    }

    /** @test */
    public function can_order_products_by_price_desc()
    {
        $cheap_product = StoreProduct::factory()->create(['price' => 1.99]);
        $average_product = StoreProduct::factory()->create(['price' => 12.99]);
        $expensive_product = StoreProduct::factory()->create(['price' => 89.99]);

        // Confirm can sort high to low (Desc)
        $this->getJson("/products?sort=high")
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('links')
                ->has('meta')
                ->has('data', 3)
                ->has('data.0', fn ($json) =>
                $json->where('price', $expensive_product->price)->etc()
                )
                ->has('data.1', fn ($json) =>
                $json->where('price', $average_product->price)->etc()
                )
                ->has('data.2', fn ($json) =>
                $json->where('price', $cheap_product->price)->etc()
                )
            );
    }

    /** @test */
    public function can_order_products_by_release_date_asc()
    {
        $old_release = StoreProduct::factory()->create(['release_date' => now()->subYear()]);
        $mid_release = StoreProduct::factory()->create(['release_date' => now()->subMonth()]);
        $new_release = StoreProduct::factory()->create(['release_date' => now()->subWeek()]);

        // Confirm can sort old to new (Asc)
        $this->getJson("/products?sort=old")
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('links')
                ->has('meta')
                ->has('data', 3)
                ->has('data.0', fn ($json) =>
                    $json->where('release_date', $old_release->release_date->format('Y-m-d'))->etc()
                )
                ->has('data.1', fn ($json) =>
                    $json->where('release_date', $mid_release->release_date->format('Y-m-d'))->etc()
                )
                ->has('data.2', fn ($json) =>
                    $json->where('release_date', $new_release->release_date->format('Y-m-d'))->etc()
                )
            );
    }

    /** @test */
    public function can_order_products_by_release_date_desc()
    {
        $old_release = StoreProduct::factory()->create(['release_date' => now()->subYear()]);
        $mid_release = StoreProduct::factory()->create(['release_date' => now()->subMonth()]);
        $new_release = StoreProduct::factory()->create(['release_date' => now()->subWeek()]);

        // Confirm can sort new to old (Desc)
        $this->getJson("/products?sort=new")
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('links')
                ->has('meta')
                ->has('data', 3)
                ->has('data.0', fn ($json) =>
                $json->where('release_date', $new_release->release_date->format('Y-m-d'))->etc()
                )
                ->has('data.1', fn ($json) =>
                $json->where('release_date', $mid_release->release_date->format('Y-m-d'))->etc()
                )
                ->has('data.2', fn ($json) =>
                $json->where('release_date', $old_release->release_date->format('Y-m-d'))->etc()
                )
            );
    }

    /** @test */
    public function can_order_products_by_name_asc(){}

    /** @test */
    public function can_order_products_by_name_desc(){}

    /** @test */
    public function products_default_ordered_when_no_sort_specified(){}
}
