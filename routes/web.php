<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::prefix('api/v1')->group(function () {
    Route::post('auth/register',       'UserController@register');
    Route::post('auth/login',          'UserController@login');
    Route::post('auth/password/reset', 'UserController@resetPassword');
    Route::get('/',function(){
        return abort(404);
    });
});
Route::middleware(['auth.token'])->prefix('api/v1')->group(function () {
    Route::post('image/upload',     'ImageController@upload');
});