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
