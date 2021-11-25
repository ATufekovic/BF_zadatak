<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealGetRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use App\Models\Meal;
use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use stdClass;

class SearchController extends Controller
{
    /**
     * Searches the set DB using the given query parameters.
     * GET parameters can be:
     * - lang (required) - shorthand language locale, refer to config/translatable.php for available locales. e.g. ("hr","en","de")
     * - page (optional) - the selected page, if outside of pagination scope then the function returns nothing. e.g. (>=1)
     * - per_page (optional) - how many items can populate a page, last page can be partially empty e.g. (>=1)
     * - category (optional) - what category do the items need to belong to, items can only have one category or none. e.g.("1", "22", "NULL", "!NULL")
     * - tags (optional) - what tags do the items need to carry, can have none or multiple tags. e.g.("1","2,3",...)
     * - with (optional) - what extra information to display when searching for items. can only be ("tags"|"category"|"ingredients"). e.g.("tags,ingredients")
     * - diff_time (optional) - UNIX timestamp that is used to see if an item is "created", "modified" or "deleted" via the created_at, etc. fields.
     *
     * @param MealGetRequest $request
     * @return JsonResponse
     */
    public function getMeals(MealGetRequest $request): JsonResponse{
        if(isset($request->validator) && $request->validator->fails()){
            return response()->json($request->validator->messages(), 400);
        }
        $params = $request->validated();
        $params = $this->setDefaultParams($params);
        $modelObject = new Meal();
        $objects = $modelObject->getMealsByParams($params);

        if(!is_null($objects)){
            $data = $this->organizeForOutput($objects, $params["lang"], $params["with"], $params["diff_time"]);
            $numOfObjects = $objects->total();
            $pages = $objects->lastPage();
        } else {
            $data = ["content" => [], "currentPageNumber" => $params["page"], "nextPageUrl" => null, "previousPageUrl" => null];
            $numOfObjects = 0;
            $pages = 1;
        }

        //organize data and drop it off as JSON
        $meta = new stdClass();
        $meta->current_page = (int) $params["page"];
        $meta->totalItems = $numOfObjects;
        $meta->itemsPerPage = (int) $params["per_page"];
        $meta->totalPages = $pages;

        $links = new stdClass();
        $this->setLinks($links, $data, $params);

        $output = new stdClass();
        $output->meta = $meta;
        $output->data = $data["content"];
        $output->links = $links;

        return response()->json($output, 200);
    }

    /**
     * Function to organize given items like meals into forms suitable for responses.
     * @param LengthAwarePaginator $meals
     * @param string $lang Language to translate to, has to be supported
     * @param array|null $with GET query parameter with
     * @param int|null $diff_time UNIX timestamp
     * @return array
     */
    private function organizeForOutput(LengthAwarePaginator $meals, string $lang, ?array $with, ?int $diff_time): array{
        $result = [];
        $counter = 0;
        foreach ($meals as $meal){
            $temp = new stdClass();
            $temp->id = $meal->id;
            $temp->title = $meal->translate($lang)->title;
            $temp->description = $meal->translate($lang)->description;
            $temp->status = $meal->getStatus($diff_time);
            $meal->setDetails($temp, $with, $lang);
            $result[$counter] = $temp;
            $counter++;
        }
        //this will disassociate the Collection from the end result
        return ["content" => $result, "currentPageNumber" => $meals->currentPage(), "nextPageUrl" => $meals->nextPageUrl(), "previousPageUrl" => $meals->previousPageUrl()];
    }

    /**Function to count the total number of items searched depending on the parameters given. Uses "category" and "tags" parameters.
     * @param array $params
     * @return int
     */
    private function numberOfObjectsByParams(array $params):int{
        $numOfMeals = 0;

        if(is_null($params["category"]) && empty($params["tags"])){//no categories and no tags
            $numOfMeals = Meal::count();
        } elseif (!is_null($params["category"]) && empty($params["tags"])){//only categories
            if($params["category"] != "NULL" && $params["category"] != "!NULL"){//all categories
                $numOfMeals = Meal::where("category_id", $params["category"])->count();
            } else if ($params["category"] == "!NULL"){//all but empty ones
                $numOfMeals = Meal::where("category_id", "!=", null)->count();
            } else if($params["category"] == "NULL") {//only empty ones
                $numOfMeals = Meal::where("category_id", null)->count();
            }
        } elseif (is_null($params["category"]) && !empty($params["tags"])){//only tags
            $numOfMeals = Meal::whereHas("tags", function($query) use ($params){
                $query->whereIn("id", $params["tags"]);
            })->count();
        } else {//both categories and tags
            $data = null;
            if($params["category"] != "NULL" && $params["category"] != "!NULL"){
                $data = Meal::where("category_id", $params["category"]);
            } else if ($params["category"] == "!NULL"){
                $data = Meal::where("category_id", "!=", null);
            } else if($params["category"] == "NULL") {
                $data = Meal::where("category_id", null);
            }
            $numOfMeals = $data->whereHas("tags", function($query) use ($params){
                $query->whereIn("id", $params["tags"]);
            })->count();
        }
        return $numOfMeals;
    }

    /**Function for pagination, uses $firstRow and $finalRow to determine which rows need to be taken from an ordered table to properly display data on a given page.
     * @param array $params
     * @param int $firstRow
     * @param int $finalRow
     * @return Builder|null
     */
    private function extractByCategory(array $params, int $firstRow, int $finalRow):?Builder{
        $data = null;
        if($params["category"] != "NULL" && $params["category"] != "!NULL"){
            $data = Meal::where("category_id", $params["category"])->orderBy("id")->skip($firstRow - 1)->take($finalRow - $firstRow + 1);
        } else if ($params["category"] == "!NULL"){
            $data = Meal::where("category_id", "!=", null)->orderBy("id")->skip($firstRow - 1)->take($finalRow - $firstRow + 1);
        } else if($params["category"] == "NULL") {
            $data = Meal::where("category_id", null)->orderBy("id")->skip($firstRow - 1)->take($finalRow - $firstRow + 1);
        }
        return $data;
    }

    private function setDefaultParams(array $params): array
    {
        //has to have all fields either by given value or by default value
        $results = [];
        $results["lang"] = $params["lang"];

        $results["per_page"] = $params["per_page"] ?? 5;
        $results["page"] = $params["page"] ?? 1;
        $results["category"] = $params["category"] ?? null;
        $results["diff_time"] = $params["diff_time"] ?? null;

        $results["tags"] = "";
        if(array_key_exists("tags", $params)){
            //if it exists, try to split it by ","
            $results["tags"] = explode(",", $params["tags"]);
        }else {
            $results["tags"] = null;
        }

        $results["with"] = "";
        if(array_key_exists("with", $params)){
            //if it exists, try to split it by ","
            $results["with"] = array_unique(explode(",", $params["with"]));
        }else {
            $results["with"] = null;
        }

        return $results;
    }

    private function setLinks(stdClass &$links, array $data, array $params): void
    {
        $baseLink = url()->current();
        $links->self = $baseLink . $this->buildQuery($params);

        if (is_null($data["previousPageUrl"])) {
            $links->prev = null;
        } else {
            $params["page"] -= 1;
            $links->prev = $baseLink . $this->buildQuery($params);
            $params["page"] += 1;
        }

        if (is_null($data["nextPageUrl"])) {
            $links->next = null;
        } else {
            $params["page"] += 1;
            $links->next = $baseLink . $this->buildQuery($params);
            $params["page"] -= 1;
        }
    }

    private function buildQuery($params): string
    {
        $query = "?";
        foreach ($params as $key => $value) {
            if (is_null($value)) {
                continue;
            } else {
                $query .= $key;
            }

            if (is_array($value)) {
                $csvString = implode(",", $value);
                $query .= "=" . $csvString;
            } else {
                $query .= "=" . $value;
            }

            $query .= "&";
        }
        return rtrim($query, "&");
    }
}


