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

// AdWords Commands
Route::post('spending', 'AdWordsController@spending');
Route::post('pause', 'AdWordsController@pause');
Route::post('enable', 'AdWordsController@enable');

// WildJar Commands
Route::post('calls', 'WildJarController@calls');
