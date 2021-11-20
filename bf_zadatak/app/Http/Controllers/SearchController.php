<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\Meal;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Boolean;
use stdClass;

class SearchController extends Controller
{
    public function search(Request $request):Response{
        $this->validateRequest($request);
        $params = $this->gatherParameters($request);

        if(!$this->checkIfLangExists($params["lang"])){//only hard check, everything else seems optional
            return $this->abortResponse("No such language exists");
        }

        $meals = $this->getDataByParams($params);
        if(!is_null($meals)){
            $data = $this->organizeForOutput($meals, $params["lang"], $params["with"], $params["diff_time"]);
        } else {
            $data = [];
        }

        //organize data and drop it off with JSON header
        $meta = new stdClass();
        $numOfMeals = $this->numberOfObjectsByNull($params);
        $pages = (integer) ceil($numOfMeals / $params["per_page"]);//eg. for 7 meals, and 3 meals per page you get 3 pages
        $meta->current_page = (int) $params["page"];
        $meta->totalItems = $numOfMeals;
        $meta->itemsPerPage = (int) $params["per_page"];
        $meta->totalPages = $pages;

        $links = new stdClass();
        $baseLink = "http://localhost/exercise/BF_zadatak/bf_zadatak/public/search";
        $links->self = $this->constructLinkFromParams($params, 0, "page", $baseLink);
        $links->next = $this->constructLinkFromParams($params, 1, "page", $baseLink);
        $links->previous = $this->constructLinkFromParams($params, -1, "page", $baseLink);

        $output = new stdClass();
        $output->meta = $meta;
        $output->data = $data;
        $output->links = $links;

        $result = response(json_encode($output), 200);
        $result->header("Content-Type", "application/json");
        return $result;
    }

    private function validateRequest(Request $request){
        $rules = [
            "lang" => "string|required",
            "per_page" => "numeric|gte:1",
            "page" => "numeric|gte:1",
            "category" => ["regex:/(^!{0,1}NULL$)|(^\d+$)/"],
            "tags" => ["regex:/^([0-9]+)(,[0-9]+){0,}?$/"],
            "with" => ["regex:/^(((tags|category|ingredients),){1,2}(tags|category|ingredients))$|^(tags|category|ingredients)$/"],//does not cover repetition
            "diff_time" => "numeric|gte:0"
        ];
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            dd(["Validation failed", $validator->failed()]);//needs to change, but good for now
        }

    }

    private function gatherParameters(Request $request, string $separator = ","): array
    {
        $lang = $request->query("lang");
        $per_page = ($request->exists("per_page")) ? $request->query("per_page") : 5;
        $page = ($request->exists("page")) ? $request->query("page") : 1;
        $category = ($request->exists("category")) ? $request->query("category") : null;
        $diff_time = ($request->exists("diff_time")) ? $request->query("diff_time") : null;

        if($request->exists("tags")){//split by ","
            $tagParamString = $request->query("tags");
            $tags = explode($separator,$tagParamString);
        } else {
            $tags = null;
        }

        if($request->exists("with")){//split by ","
            $withParamString = $request->query("with");
            $with = explode($separator,$withParamString);
        } else {
            $with = [];
        }

        return [
            "lang" => $lang,
            "per_page" => $per_page,
            "page" => $page,
            "category" => $category,
            "tags" => $tags,
            "with" => $with,
            "diff_time" => $diff_time
        ];
    }

    private function abortResponse($text):Response{
        $data = "Error: " . $text;
        $result = response($data, 400);
        $result->header("Content-Type", "text/plain");
        return $result;
    }

    private function checkIfLangExists($lang):bool{
        $locals = Config::get("translatable.locales");
        return in_array($lang, $locals);
    }

    private function getDataByParams($params){
        $data = null;

        //conditional so that it doesn't need to sift through all meals, what if there were thousands of meals?
        $numOfMeals = $this->numberOfObjectsByNull($params);

        $pages = (integer) ceil($numOfMeals / $params["per_page"]);//eg. for 7 meals, and 3 meals per page you get 3 pages [3,3,1]
        $firstRow = null;
        $finalRow = null;
        if($pages == 1) {//no fuss no muss
            $firstRow = 1;
            $finalRow = $numOfMeals;
        } elseif ($params["page"] == $pages){//last page might be not complete, i.e. only 1 row instead of per_page
            $finalRow = $numOfMeals;
            $firstRow = ($pages - 1) * $params["per_page"] + 1;//in example result is (3 - 1) * 3 + 1 = 7, so it will fetch from row 7 to 7
        } elseif ($params["page"] > $pages) {
            return null;//no data is on these pages, return empty object
        } else {
            $finalRow = $params["page"] * $params["per_page"];//for second page of previous example, we need rows 4,5,6, so 6 is the integer we are looking for
            if($finalRow > $numOfMeals){
                $finalRow = $numOfMeals;
            }
            $firstRow = $finalRow - $params["per_page"] + 1;//deduct final with num per page and add 1 to get the "first" row of the page
        }
        //continuing the example, with skip($firstRow - 1) we skip rows 1,2,3 since $firstRow would be 4
        //and then using take($finalRow - $firstRow + 1), we can get our 4th, 5th and 6th rows which we need (6 - 4 + 1 = 3)

        //dd(Meal::where("category_id","!=", null)->get());

        //check by category and/or tags or none
        if(!is_null($params["category"]) && !is_null($params["tags"])){
            $data = $this->extractByCategory($params, $firstRow, $finalRow);
            $data = $data->whereHas("tags", function($query) use ($params){
                $query->whereIn("id", $params["tags"]);
            })->get();
        } elseif (!is_null($params["category"]) && is_null($params["tags"])) {
            $data = $this->extractByCategory($params, $firstRow, $finalRow)->get();
        } elseif (is_null($params["category"]) && !is_null($params["tags"])){
            $data =Meal::whereHas("tags", function($query) use ($params){
                $query->whereIn("id", $params["tags"]);
            })->orderBy("id")->skip($firstRow - 1)->take($finalRow - $firstRow + 1)->get();
        }else{
            $data = Meal::orderBy("id")->skip($firstRow - 1)->take($finalRow - $firstRow + 1)->get();
        }

        return $data;
    }

    private function organizeForOutput(Collection $meals, string $lang, array $with, $diff_time):array{
        $result = [];
        $counter = 0;
        foreach ($meals as $meal){
            $temp = new stdClass();
            $temp->id = $meal->id;
            $temp->title = $meal->translate($lang)->title;
            $temp->description = $meal->translate($lang)->description;

            if(is_null($diff_time)){
                $temp->status = "created";
            } else {
                //$objectCreatedAt = $meal->created_at->getTimestamp();//if diff_time is before updated, it's "created"
                $objectUpdatedAt = $meal->updated_at->getTimestamp();//if diff_time is after updated but before deleted, its "modified"
                if(is_null($meal->deleted_at)){
                    if($diff_time < $objectUpdatedAt){
                        $temp->status = "created";
                    } else {
                        $temp->status = "modified";
                    }
                } else {
                    $objectDeletedAt = $meal->deleted_at->getTimestamp();//if it's after deleted, it's "deleted"
                    if($diff_time < $objectUpdatedAt){
                        $temp->status = "created";
                    } elseif ($diff_time < $objectDeletedAt){
                        $temp->status = "modified";
                    } else {
                        $temp->status = "deleted";
                    }
                }
            }

            //check against repeated tags/ingredients/category in $with just in case
            $check = [];
            foreach ($with as $item){
                $check[$item] = true;
            }

            if(isset($check["category"])){
                if(!is_null($meal->category_id)){
                    $categoryId = $meal->category_id;
                    $category = Category::find($categoryId);
                    $temp->category = $this->getAttributes($category, $lang);
                } else {
                    $temp->category = null;
                }
            }

            if(isset($check["tags"])){
                $tags = $meal->tags()->get();
                $tagCounter = 0;
                $temp->tags = [];
                foreach ($tags as $tag){
                    $temp->tags[$tagCounter] = $this->getAttributes($tag, $lang);

                    $tagCounter++;
                }
            }

            if(isset($check["ingredients"])){
                $ingredients = $meal->ingredients()->get();
                $ingredientCounter = 0;
                $temp->ingredients = [];
                foreach ($ingredients as $ingredient){
                    $temp->ingredients[$ingredientCounter] = $this->getAttributes($ingredient, $lang);

                    $ingredientCounter++;
                }
            }

            $result[$counter] = $temp;
            $counter++;
        }
        return $result;
    }

    private function getAttributes($object, $lang):object{
        $container = new stdClass();
        $container->id = $object->id;
        $container->title = $object->translate($lang)->title;
        $container->slug = $object->slug;

        return $container;
    }

    private function numberOfObjectsByNull($params):int{
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

    private function extractByCategory($params, $firstRow, $finalRow){
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

    private function constructLinkFromParams(array $params, int $offset, string $offsetKey, string $baseLink):string
    {
        //check for start and end of "page travel
        if($params[$offsetKey] + $offset < 1){
            return "null";
            //$offset = 1 - $params[$offsetKey];//set page to 1 if it tries to go under 1
        } else {
            $numOfMeals = $this->numberOfObjectsByNull($params);
            $pages = (integer) ceil($numOfMeals / $params["per_page"]);
            if($params[$offsetKey] + $offset > $pages){
                return "null";
            }
        }
        $result = $baseLink . "?";
        $numItems = count($params);
        $iteration = 0;
        foreach ($params as $key => $value){
            if(empty($value)){
                $iteration++;
                $numItems--;
                continue;
            }
            $result .= $key . "=";
            if(!is_array($value)){
                if($key === $offsetKey){
                    $result .= ((int)$value + $offset);
                } else {
                    $result .= $value;
                }
            } else {
                $internalNumItems = count($value);
                $internalIteration = 0;
                foreach($value as $val){
                    $result .= $val;
                    if(++$internalIteration !== $internalNumItems){
                        //on last item don't add ","
                        $result .= ",";
                    }
                }
            }

            if(++$iteration !== $numItems){
                //on last item don't add "&"
                $result .= "&";
            }
        }
        $result = rtrim($result, "&");
        return $result;
    }
}


