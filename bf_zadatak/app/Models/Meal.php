<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use stdClass;

class Meal extends Model
{
    use HasFactory, Translatable, SoftDeletes;

    public $translatedAttributes = ["title", "description"];

    public function ingredients()
    {
        //many-to-many for table "ingredients"
        return $this->belongsToMany(Ingredient::class);
    }

    public function tags()
    {
        //many-to-many for table "tags"
        return $this->belongsToMany(Tag::class);
    }

    public function category()
    {
        //one-to-many (category-to-meals) for table "categories"
        return $this->belongsTo(Category::class);
    }

    public function getMealsByParams($params): ?LengthAwarePaginator
    {
        $results = $this;

        //handle 'with' for display of information
        if (!is_null($params["with"])) {
            //array_unique should handle any duplicates
            $results = $results->with($params["with"]);
        }

        //handle 'category' filtration
        if ($params["category"] === "NULL") {
            $results = $results->where("category_id", "=", null);
        } elseif ($params["category"] === "!NULL") {
            $results = $results->where("category_id", "!=", null);
        } elseif(!is_null($params["category"])) {
            $results = $results->where("category_id", "=", $params["category"]);
        }

        //handle 'tags' filtration
        if(!is_null($params["tags"])) {
            foreach($params["tags"] as $tag){
                $results = $results->whereHas("tags", function($query) use ($tag){
                    $query->where("id", "=", $tag);
                });
            }
        }

        //handle pagination
        $results = $results->paginate($params["per_page"], ["*"], "page", $params["page"]);
        return $results;
    }

    public function getStatus($diff_time)
    {
        return "created";//TODO: finish stub
    }

    public function setDetails(object &$temp, array $with, string $lang): void
    {
        //handle category
        if (in_array("category", $with)) {
            $temp->category = $this->getCategoryDetails($lang);
        }
        //handle tags
        if (in_array("tags", $with)) {
            $temp->tags = $this->getTagsDetails($lang);
        }
        //handle ingredients
        if (in_array("ingredients", $with)) {
            $temp->ingredients = $this->getIngredientsDetails($lang);
        }
    }

    private function getCategoryDetails(string $lang): object
    {
        $category = new stdClass();

        if($this->category_id === null){
            //category may be NULL
            return $category;
        } else {
            $this->category->getDetails($category, $lang);
        }

        return $category;
    }

    private function getTagsDetails(string $lang): array
    {
        $tags = [];
        $tagCounter = 0;

        foreach ($this->tags as $tag) {
            $tags[$tagCounter] = new stdClass();
            $tag->getDetails($tags[$tagCounter], $lang);
            $tagCounter++;
        }

        return $tags;
    }

    private function getIngredientsDetails(string $lang)
    {
        $ingredients = [];
        $ingredientCounter = 0;

        foreach ($this->ingredients as $ingredient){
            $ingredients[$ingredientCounter] = new stdClass();
            $ingredient->getDetails($ingredients[$ingredientCounter], $lang);
            $ingredientCounter++;
        }

        return $ingredients;
    }
}
