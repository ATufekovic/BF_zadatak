<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IngredientTranslations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ingredient_translations', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string("locale")->index();

            $table->bigInteger("ingredient_id")->unsigned();
            $table->unique(["ingredient_id","locale"]);
            $table->foreign("ingredient_id")->references("id")->on("ingredients")->onDelete("cascade");

            $table->string("title");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ingredient_translations');
    }
}
