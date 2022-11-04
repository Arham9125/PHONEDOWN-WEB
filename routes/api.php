<?php

use App\Http\Controllers\Api\ChildrenApiController;
use App\Http\Controllers\Api\ParentApiController;
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

Route::prefix('guardian/')->group(function(){
    Route::post('login',[ParentApiController::class,'login']);
    Route::post('register',[ParentApiController::class,'register']);

    Route::middleware('parentApiAuthorization')->group(function () {
        Route::get('get-family-member',[ParentApiController::class,'get_family_members']);
        Route::post('add-member',[ParentApiController::class,'add_member']);
        Route::get('get-all-relationships',[ParentApiController::class,'get_all_relationships']);
        Route::post('logout',[ParentApiController::class,'logout']);
        Route::post('update/profile',[ParentApiController::class,'update_profile']);
    });
});

Route::prefix('child/')->group(function(){
    Route::post('login',[ChildrenApiController::class,'login']);

    Route::middleware('childApiAuthorization')->group(function () {
        Route::get('get-family-member',[ChildrenApiController::class,'get_family_members']);
        Route::post('update/profile',[ChildrenApiController::class,'update_profile']);
    });
});
