<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IngredientMeal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ingredient_meal', function (Blueprint $table) {
            $table->bigInteger("ingredient_id")->unsigned()->index();
            $table->bigInteger("meal_id")->unsigned()->index();

            $table->foreign("ingredient_id")->references("id")->on("ingredients")->onDelete("cascade");
            $table->foreign("meal_id")->references("id")->on("meals")->onDelete("cascade");

            $table->primary(array("ingredient_id", "meal_id"));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ingredient_meal');
    }
}
