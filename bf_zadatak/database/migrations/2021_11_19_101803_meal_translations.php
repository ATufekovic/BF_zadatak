<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MealTranslations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meal_translations', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->bigInteger("meal_id")->unsigned();
            $table->string("locale")->index();

            $table->string("title");
            $table->string("description");

            $table->unique(["meal_id", "locale"]);
            $table->foreign("meal_id")->references("id")->on("meals")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meal_translations');
    }
}
