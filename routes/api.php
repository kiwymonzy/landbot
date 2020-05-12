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

// FreshSales Commands
Route::post('account', 'FreshSalesController@account');

// GoogleAds Commands
Route::post('spending',       'GoogleAds\SpendingController@spending');
Route::post('pause',          'GoogleAds\StatusController@pause');
Route::post('enable',         'GoogleAds\StatusController@enable');
Route::post('billing',        'GoogleAds\BillingController@billing');
Route::post('accounts',       'GoogleAds\AccountController@accounts');
Route::post('current-budget', 'GoogleAds\BudgetController@currentBudget');
Route::post('change-budget',  'GoogleAds\BudgetController@changeBudget');

// WildJar Commands
Route::post('calls', 'WildJarController@calls');

Route::group(['prefix' => 'fs'], function () {

});
