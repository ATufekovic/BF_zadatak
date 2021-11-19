<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Meals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->bigIncrements("id");
            //$table->string("title");
            //$table->string("description");

            $table->bigInteger("category_id")->unsigned()->nullable();//a meal has a single category, thus it's a one to many relation
            $table->foreign("category_id")->references("id")->on("categories");

            //a meal can have multiple tags, thus it's a many-to-many relation
            //a meal (hopefully) has multiple ingredients, thus it's a many-to-many relation
            //both of these are defined in another table "meal_tag" and "meal_ingredient"
            //behaviour is defined in their models

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meals');
    }
}
