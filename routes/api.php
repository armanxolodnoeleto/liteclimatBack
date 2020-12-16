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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('getCategories', 'CategoriesController@getCategories');
Route::post('getProducts/{categoryId?}', 'ProductsController@getProducts');
Route::get('getProduct/{productId}', 'ProductsController@getProduct');
Route::get('getFilterData/{categoryId?}', 'ProductsController@getFilterData');
Route::post('searchProduct', 'ProductsController@searchProduct');
Route::post('checkout', 'FeedbackController@checkout');
Route::post('oneClickOrder', 'FeedbackController@oneClickOrder');
Route::post('contactUs', 'FeedbackController@contactUs');
Route::post('review', 'FeedbackController@review');
Route::post('getReviews', 'FeedbackController@getReviews');
