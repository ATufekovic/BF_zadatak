<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Meal;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Depends on tag, ingredient, category and meal factories.
     * After 10 seconds, touches the meals, and after another 10 seconds randomly deletes meals.
     *
     * @return void
     */
    public function run()
    {
        $numOfMinorObjects = 5;
        $numOfMajorObjects = 100;
        $objectCategoryChance = 80;
        $modifyObjects = true;
        $modifyChancePercent = 50;
        $deleteObjects = true;
        $deleteChancePercent = 20;

        $tags = Tag::factory()->count($numOfMinorObjects)->create();
        $ingredients = Ingredient::factory()->count($numOfMinorObjects)->create();
        $categories = Category::factory()->count($numOfMinorObjects)->create();

        $meals = Meal::factory()->count($numOfMajorObjects)->create();

        foreach ($meals as $meal) {
            //give each meal one or no random category
            $chance = rand(0,100);
            if ($chance < $objectCategoryChance) {
                //x% chance to have a category
                $randomCategory = $categories->random(1);
                $meal->category()->associate($randomCategory[0]->id);
            }

            //give each meal a random number of tags
            $randomTags = $tags->random(rand(1, $tags->count() - 1));
            foreach ($randomTags as $tag) {
                $meal->tags()->attach($tag->id);
            }

            //give each meal a random number of ingredients, but at least one
            $randomIngredients = $ingredients->random(rand(1, $ingredients->count() - 1));
            foreach ($randomIngredients as $ingredient) {
                $meal->ingredients()->attach($ingredient->id);
            }

            $meal->save();
        }

        if ($modifyObjects) {
            sleep(10);
            foreach ($meals as $meal) {
                $chance = rand(0, 100);
                if ($chance < $modifyChancePercent) {
                    //x% chance to be modified, use for testing only
                    $meal->touch();
                }
            }
        }
        if ($deleteObjects) {
            sleep(10);
            foreach ($meals as $meal) {
                $chance = rand(0, 100);
                if ($chance < $deleteChancePercent) {
                    //x% chance to be soft deleted, use for testing only
                    $meal->delete();
                }
            }
        }


    }
}
