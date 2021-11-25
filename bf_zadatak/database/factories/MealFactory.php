<?php

namespace Database\Factories;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Factories\Factory;

class MealFactory extends Factory
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
        foreach ($locales as $locale) {
            $parameters[$locale] = [
                "title" => "Title for meal (" . $counter . ") and locale: " . $locale,
                "description" => "Description for meal (" . $counter . ") and locale: " . $locale
            ];
        }
        $counter++;

        return $parameters;
    }
}
