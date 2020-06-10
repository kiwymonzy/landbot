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

// GoogleAds Commands
Route::post('spendings',       'GoogleAds\SpendingController@spendings');
Route::post('billing',        'GoogleAds\BillingController@billing');
Route::post('accounts',       'GoogleAds\AccountController@accounts');
Route::post('active-campaigns', 'GoogleAds\MutationController@activeCampaigns');
// Mutations
Route::post('change-budget',  'GoogleAds\BudgetController@changeBudget');
Route::post('pause',          'GoogleAds\StatusController@pause');
// Route::post('enable',         'GoogleAds\StatusController@enable');

// Recommendations
Route::group(['prefix' => 'recommendations'], function () {
    Route::post('/', 'GoogleAds\RecommendationController@show');
    Route::post('accept', 'GoogleAds\RecommendationController@accept');
    Route::post('decline', 'GoogleAds\RecommendationController@decline');
});

// WildJar Commands
Route::post('calls', 'WildJarController@calls');

Route::get('test', function() {
    $calls = 10;
    $change = 20.332;
    $adsCampaignBudget = 225;
    $adsCampaigns = collect([
        'Results Laser Clinic - Cosmetic Injectables WA',
        'Results Laser Clinic - Laser WA',
    ]);
    $adsAccountId = '5151053398';
    $adsCampaignBudgetId = '6670677588';
    $clientId = 30;
    dispatch(new SendRecommendation(
        $calls,
        $change,
        $adsCampaignBudget,
        $adsCampaigns,
        $adsAccountId,
        $adsCampaignBudgetId,
        $clientId
    ));
    // $lb = (new LandBot)->customer();
    // $lb->sendMessage('36409872', "Hi, we've noticed that the performance of your campaign(s) CAMPAIGN HERE have bee performing exceptionally well");
    // $lb->assignBot('36409872', '505201');
});
