<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MealIngredient extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meal_ingredient', function (Blueprint $table) {
            $table->bigInteger("meal_id")->unsigned()->index();
            $table->bigInteger("ingredient_id")->unsigned()->index();

            $table->foreign("meal_id")->references("id")->on("meals")->onDelete("cascade");
            $table->foreign("ingredient_id")->references("id")->on("ingredients")->onDelete("cascade");

            $table->primary(array("meal_id","ingredient_id"));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meal_ingredient');
    }
}
