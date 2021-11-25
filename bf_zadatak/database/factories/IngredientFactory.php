<?php

namespace Database\Factories;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Factories\Factory;

class IngredientFactory extends Factory
{
    /**
     * Define the model's default state. Uses translatable for translations.
     *
     * @return array
     */
    public function definition()
    {
        $locales = Config::get("translatable.locales");
        static $counter = 1;

        $parameters = array();
        $parameters["slug"] = "Default slug for this ingredient (" .
            $counter . "), random number:" .
            $this->faker->randomNumber(5, true);
        foreach ($locales as $locale) {
            $parameters[$locale] = [
                "title" => "Title for ingredient (" . $counter . ") and locale: " . $locale
            ];
        }
        $counter++;

        return $parameters;
    }
}
