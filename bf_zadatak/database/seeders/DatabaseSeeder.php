<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Meal;
use Illuminate\Database\Seeder;
use App\Models\Tag;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $tags = Tag::factory()->count(4)->create();
        $ingredients = Ingredient::factory()->count(4)->create();
        $categories = Category::factory()->count(4)->create();

        $meals = Meal::factory()->count(5)->create();

        foreach ($meals as $meal) {
            //give each meal one random category
            $randomCategory = $categories->random(1);
            $meal->category()->associate($randomCategory[0]->id);

            //give each meal a random number of tags
            $randomTags = $tags->random(rand(0, $tags->count() - 1));
            foreach ($randomTags as $tag){
                $meal->tags()->attach($tag->id);
            }

            //give each meal a random number of ingredients, but at least one
            $randomIngredients = $ingredients->random(rand(1, $ingredients->count() - 1));
            foreach ($randomIngredients as $ingredient){
                $meal->ingredients()->attach($ingredient->id);
            }

            $meal->save();
        }
    }
}
