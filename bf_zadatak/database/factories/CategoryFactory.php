<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;

class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $locales = Config::get("translatable.locales");
        static $counter = 1;

        $parameters = array();
        $parameters["slug"] = "Default slug for this category (" . $counter . "), random number:" . $this->faker->randomNumber(5,true);
        foreach ($locales as $locale){
            $parameters[$locale] = ["title" => "Title for category (" . $counter . ") and locale: " . $locale];
        }
        $counter++;

        return $parameters;
    }
}
