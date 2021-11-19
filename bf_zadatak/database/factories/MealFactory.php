<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "title" => $this->faker->word(),
            "description" => $this->faker->sentence(5, false),
            "numOfTags" => $this->faker->numberBetween(1,3),
            "numOfIngredients" => $this->faker->numberBetween(1,3),
            "category" => $this->faker->numberBetween(1,3)
        ];
    }
}
