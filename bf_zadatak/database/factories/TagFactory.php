<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tag;
use Illuminate\Support\Facades\Config;

class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $locales = array_keys(Config::get("locales"));
        static $counter = 1;

        $parameters["slug"] = "Default slug for this tag (" . $counter . "), random number:" . $this->faker->randomNumber(5, true);
        foreach ($locales as $locale){
            $parameters[$locale] = ["title" => "Title for tag (" . $counter . ") and locale: " . $locale];
        }
        $counter++;

        return $parameters;
    }

    protected $model = Tag::class;
}
