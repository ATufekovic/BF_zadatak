<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Pagination\LengthAwarePaginator;
use Astrotomic\Translatable\Translatable;

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

    /**
     * For a set of parameters given by the GET query, return a paginated collection of filtered objects.
     *
     * @param array $params
     * @return LengthAwarePaginator|null
     */
    public function getMealsByParams(array $params): ?LengthAwarePaginator
    {
        $results = $this;

        //handle 'with' for display of information
        if (!is_null($params["with"])) {
            //array_unique should have handled any duplicates
            $results = $results->with($params["with"]);
        }

        //handle 'category' filtration
        if ($params["category"] === "NULL") {
            $results = $results->where("category_id", "=", null);
        } elseif ($params["category"] === "!NULL") {
            $results = $results->where("category_id", "!=", null);
        } elseif (!is_null($params["category"])) {
            $results = $results->where("category_id", "=", $params["category"]);
        }

        //handle 'tags' filtration, tags are handled as AND
        if(!is_null($params["tags"])) {
            foreach ($params["tags"] as $tag) {
                $results = $results->whereHas("tags", function($query) use ($tag) {
                    $query->where("id", "=", $tag);
                });
            }
        }

        //handle diff_time so that it can fetch soft deleted rows
        if (!is_null($params["diff_time"])) {
            $results = $results->withTrashed();
        }

        //on resulting query do pagination
        $results = $results->paginate($params["per_page"], ["*"], "page", $params["page"]);
        return $results;
    }

    /**
     * Return the readiness status of the object depending on the argument $diff_time.
     * $diff_time must be a UNIX timestamp to work properly.
     *
     * @param int|null $diff_time - Unix Timestamp
     * @return string - one of "created", "modified", "deleted"
     */
    public function getStatus(?int $diff_time): string
    {
        $result = "";
        if (is_null($diff_time)) {
            $result = "created";
        } elseif ($diff_time <= $this->updated_at->timestamp) {
            //Up until exactly this point it was still "fresh", so we use lte.
            //It was not specified how to handle if diff_time is before
            //created_at, so this condition covers that as well.
            $result = "created";
        } elseif (is_null($this->deleted_at) && ($diff_time > $this->updated_at->timestamp)) {
            //check if it was ever deleted, since soft deletion
            //updates "updated_at" column
            $result = "modified";
        } elseif ((!is_null($this->deleted_at)) && ($diff_time > $this->updated_at->timestamp)) {
            //if deleted, updated_at equals deleted_at
            $result = "deleted";
        }
        return $result;
    }

    /**
     * Using the given object $temp, the method attempts to fill it with information about itself according to
     * the $with array. It translates using the $lang locale.
     *
     * @param object $temp
     * @param array|null $with
     * @param string $lang - one of the system locales
     */
    public function setDetails(object &$temp, ?array $with, string $lang): void
    {
        if (is_null($with)) {
            return;
        }
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

    /* NOTE: The following three functions break DRY.
     * They do so because in the future their definition may change, i.e. an
     * ingredient may get a "warning" column to warn about the presence of
     * allergens in the food, a category may change to have more columns with
     * more text to describe it better, etc.
     * Additionally, tags and ingredients are many-to-many, thus no interface
     * can match all three models.*/

    /**
     * Function to get details about the objects "category" into a container
     * object to return it. Object may be null, so it can return an empty object.
     *
     * @param string $lang - one of the system locales
     * @return object
     */
    private function getCategoryDetails(string $lang): object
    {
        $category = new stdClass();

        if ($this->category_id === null) {
            //category may be NULL
            return $category;
        } else {
            $this->category->getDetails($category, $lang);
        }

        return $category;
    }

    /**
     * Function to get details about the objects "tags" into container objects
     * to return it. It will always have at least one tag, so it can't return an
     * empty object.
     *
     * @param string $lang - one of the system locales
     * @return array
     */
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

    /**
     * Function to get details about the objects "ingredients" into container
     * objects to return it. It will always have at least one ingredient, so it
     * can't return an empty object.
     *
     * @param string $lang - one of the system locales
     * @return array
     */
    private function getIngredientsDetails(string $lang): array
    {
        $ingredients = [];
        $ingredientCounter = 0;

        foreach ($this->ingredients as $ingredient) {
            $ingredients[$ingredientCounter] = new stdClass();
            $ingredient->getDetails($ingredients[$ingredientCounter], $lang);
            $ingredientCounter++;
        }

        return $ingredients;
    }
}
