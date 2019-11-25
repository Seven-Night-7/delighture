<?php

use Illuminate\Http\Request;

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

//  登录
Route::post('login', 'AuthenticationController@store');

Route::middleware('token.check')->group(function () {
    //  注销登录
    Route::delete('logout', 'AuthenticationController@destroy');
    //  我的登录信息
    Route::get('/users/me', 'UserController@me');
});
