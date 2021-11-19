<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "title" => $this->faker->sentence(4, false),
            "slug" => "Default slug for this ingredient, random number: " . $this->faker->randomNumber(5, true)
        ];
    }
}
