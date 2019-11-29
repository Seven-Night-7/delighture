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
Route::post('auths', 'AuthenticationController@store');
//  注销登录
Route::delete('auths', 'AuthenticationController@destroy');

Route::middleware([
    'refresh.token',
])->group(function () {
    //  我的登录信息
    Route::get('auths/me', 'AuthenticationController@me');

    //  - 用户
    Route::get('users', 'UserController@index');
    Route::post('users', 'UserController@store');

    //  - 冻结的用户
    Route::post('frozen-users/{user}', 'UserController@freeze');
    Route::delete('frozen-users/{user}', 'UserController@unfreeze');
});
