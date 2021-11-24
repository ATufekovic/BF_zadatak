<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
/**
 * Single primary endpoint, supports GET queries, more info inside the "search" function
 * @link ../app/Http/Controllers/SearchController.php
 */
Route::get("/getMeals", [App\Http\Controllers\SearchController::class, "getMeals"]);
