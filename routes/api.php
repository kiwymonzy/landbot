<?php

use App\Jobs\SendRecommendation;
use App\Library\LandBot\LandBot;
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

Route::post('statistics', 'StatisticsController@statistics');

// GoogleAds Commands
Route::post('spendings', 'GoogleAds\SpendingController@spendings');
Route::post('billing', 'GoogleAds\BillingController@billing');
Route::post('accounts', 'GoogleAds\AccountController@accounts');
Route::post('active-campaigns', 'GoogleAds\MutationController@activeCampaigns');

// Mutations
Route::post('change-budget', 'GoogleAds\BudgetController@changeBudget');
Route::post('pause', 'GoogleAds\StatusController@pause');
// Route::post('enable', 'GoogleAds\StatusController@enable');'

// Gravity forms
Route::get('gravity-forms', 'FormNotificationController@latestNotification');
Route::post('gravity-forms', 'FormNotificationController@saveAndNotify');

// Recommendations
Route::group(['prefix' => 'recommendations'], function () {
    Route::post('/', 'GoogleAds\RecommendationController@show');
    Route::post('accept', 'GoogleAds\RecommendationController@accept');
    Route::post('decline', 'GoogleAds\RecommendationController@decline');
});

// WildJar Commands
Route::post('calls', 'WildJarController@calls');

Route::group(['prefix' => 'dashboard'], function() {
    Route::group(['prefix' => 'client'], function() {
        Route::get('requests', 'Dashboard\ClientController@requests');
        Route::get('mutations', 'Dashboard\ClientController@mutations');
        Route::get('count', 'Dashboard\ClientController@count');
    });
    Route::group(['prefix' => 'statistics'], function() {
        Route::get('count', 'Dashboard\StatisticsController@count');
    });
    Route::group(['prefix' => 'recommendations'], function() {
        Route::get('count', 'Dashboard\RecommendationController@count');
    });
    Route::group(['prefix' => 'google'], function() {
        Route::get('budget-increase', 'Dashboard\GoogleAdsController@budgetIncreaseCount');
        Route::get('pause', 'Dashboard\GoogleAdsController@pauseCount');
    });
});

// PDF
Route::get('pdf/{account}', 'PDFController@store');
