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

// GoogleAds Commands
Route::post('spending', 'GoogleAdsController@spending');
Route::post('pause', 'GoogleAdsController@pause');
Route::post('enable', 'GoogleAdsController@enable');

// WildJar Commands
Route::post('calls', 'WildJarController@calls');
