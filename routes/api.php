<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
Route::post('/register','AuthController@register');
Route::post('/login','AuthController@login');
Route::post('/logout','AuthController@logout');
Route::put('/update-info','AuthController@updateinfo')->middleware('auth:api');
Route::get('/get-info','AuthController@getinfo')->middleware('auth:api');
Route::post('/get-detail','AuthController@get_detail')->middleware('auth:api');
Route::post('/request-reset-password','AuthController@request_resetPassword');
Route::post('/reset-password','AuthController@resetPassword');