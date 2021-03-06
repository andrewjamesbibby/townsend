<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreProduct>
 */
class StoreProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'store_id'           => 3,
            'artist_id'          => 1,
            'type'               => $this->faker->randomElement(['music', 'download', 'ticket', 'multi', 'free']),
            'name'               => $this->faker->sentence(3),
            'launch_date'        => '0000-00-00 00:00:00',
            'remove_date'        => '0000-00-00 00:00:00',
            'release_date'       => '2015-03-06',
            'description'        => $this->faker->sentence(10),
            'available'          => 1,
            'price'              => (string) $this->faker->randomFloat(2, 1, 50),
            'euro_price'         => (string) $this->faker->randomFloat(2, 1, 50),
            'dollar_price'       => (string) $this->faker->randomFloat(2, 1, 50),
            'image_format'       => 'jpg',
            'deleted'            => 0,
            'disabled_countries' => '',
            'display_name'       => $this->faker->sentence(3),
            'created_at'         => now(),
            'updated_at'         => now(),
            'position'           => null,
        ];
    }

    /**
     * Indicate the product is unavailable
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unavailable()
    {
        return $this->state(function (array $attributes) {
            return [
                'available' => 0,
            ];
        });
    }

    /**
     * Indicate the product is deleted
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function deleted()
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted' => 1,
            ];
        });
    }
}
