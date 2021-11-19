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
            $table->increments("id");
            $table->integer("ingredient_id")->unsigned();
            $table->string("locale")->index();

            $table->string("title");
            $table->string("slug");

            $table->unique(["article_id","locale"]);
            $table->foreign("article_id")->references("id")->on("ingredients")->onDelete("cascade");
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
