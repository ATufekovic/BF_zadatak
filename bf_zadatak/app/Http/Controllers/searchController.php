<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Meal;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Boolean;

class searchController extends Controller
{
    public function search(Request $request){
        $this->validateRequest($request);
        $params = $this->gatherParameters($request);

        if(!$this->checkIfLangExists($params["lang"])){//only hard check, everything else seems optional
            return $this->abortResponse("No such language exists");
        }

        $meals = $this->getDataByParams($params);

        dd($meals);

        //organize data and drop it off with JSON header
        $data = ["meta" => [
            "current_page"=> 1,
            "totalItems" => 1,
            "itemsPerPage" => $params["per_page"],
            "totalPages" => 1
        ]];
        $result = response($data, 200);
        $result->header("Content-Type", "application/json");
        return $result;
    }

    private function validateRequest(Request $request){
        $rules = [
            "lang" => "string|required",
            "per_page" => "numeric",
            "page" => "numeric",
            "category" => "numeric",
            "tags" => ["regex:/^([0-9]+)(,[0-9]+){0,}?$/"],
            "with" => "in:tags,category,ingredients",
            "diff_time" => "numeric"
        ];
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            dd($validator->failed());//needs to change, but good for now
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
            $with = null;
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

        //first we check by both category and tag
        if(!is_null($params["category"]) && !is_null($params["tags"])){
            $data = Meal::where("category_id", $params["category"]);

            $data = $data->whereHas("tags", function($query) use ($params){
                $query->whereIn("id", $params["tags"]);
            })->get();
        } elseif (!is_null($params["category"]) && is_null($params["tags"])) {
            $data = Meal::where("category_id", $params["category"])->get();
        } elseif (is_null($params["category"]) && !is_null($params["tags"])){
            $data =Meal::whereHas("tags", function($query) use ($params){
                $query->whereIn("id", $params["tags"]);
            })->get();
        }else{
            $data = Meal::get();
        }

        return $data;
    }
}


