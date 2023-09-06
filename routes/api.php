<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\CountriesController;
use App\Http\Controllers\CustomFieldsController;
use App\Http\Controllers\DataFilesController;
use App\Http\Controllers\MobileNetworksController;
use App\Http\Controllers\OffersController;
use App\Http\Controllers\SegmentsController;
use App\Http\Controllers\SmsCampaignOffersController;
use App\Http\Controllers\SmsCampaignsController;
use App\Http\Controllers\SmsCampaignSenderidsController;
use App\Http\Controllers\SmsCampaignTextsController;
use App\Http\Controllers\SmsRoutingCompaniesController;
use App\Http\Controllers\SmsRoutingPlanRulesController;
use App\Http\Controllers\SmsRoutingPlansController;
use App\Http\Controllers\SmsRoutingRatesController;
use App\Http\Controllers\SmsRoutingRoutesController;
use App\Http\Controllers\SmsRoutingSmppConnectionsController;
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

Route::prefix('v1')->group(function () {

    Route::prefix('token')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('/user', [AuthController::class, 'me']);
        Route::get('/user/teams', [AuthController::class, 'teams']);

        Route::get('/data-files', [DataFilesController::class, 'index']);
        Route::get('/data-files/{id:uuid}/sample', [DataFilesController::class, 'sample']);
        Route::post('/data-files/contacts/upload-file', [DataFilesController::class, 'uploadContacts']);
        Route::post('/data-files/{id:uuid}/import', [DataFilesController::class, 'startImport']);

        Route::resource('custom-fields', CustomFieldsController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('segments', SegmentsController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('segments/fields', [SegmentsController::class, 'fields']);
        Route::post('segments/preview', [SegmentsController::class, 'preview']);

        Route::resource('audience/contacts', ContactsController::class)
            ->only(['index']);

        Route::get('countries', [CountriesController::class, 'index']);

        Route::prefix('sms')->name('sms.')->group(function () {
            Route::prefix('routing')->name('routing.')->group(function () {

                Route::resource('networks', MobileNetworksController::class)->only(['index']);
                Route::resource('companies', SmsRoutingCompaniesController::class)
                    ->only(['index', 'store']);

                Route::resource('routes', SmsRoutingRoutesController::class)
                    ->only(['index', 'store', 'destroy']);

                Route::prefix('routes')->name('routes.')->group(function () {
                    Route::post('smpp-connections', [SmsRoutingSmppConnectionsController::class, 'store'])
                        ->name('smpp-connections.store');
                    Route::post('smpp-connections/test', [SmsRoutingSmppConnectionsController::class, 'test'])
                        ->name('smpp-connections.test');
                    Route::get('smpp-connections/{smpp_connection}/view',
                        [SmsRoutingSmppConnectionsController::class, 'show'])
                        ->name('smpp-connections.show');
                });

                Route::group(['prefix' => 'plans/{plan}', 'as' => 'plans.'], function () {
                    Route::resource('rules', SmsRoutingPlanRulesController::class)
                        ->only(['index', 'store', 'destroy', 'update', 'show']);
                    Route::post('rules/split', [SmsRoutingPlanRulesController::class, 'storeSplitRule'])
                        ->name('rules.split');
                    Route::put('rules/{rule}/split', [SmsRoutingPlanRulesController::class, 'patchSplitRule'])
                        ->name('rules.split.patch');
                });

                Route::resource('plans', SmsRoutingPlansController::class)
                    ->only(['index', 'store', 'destroy', 'update', 'show']);
                Route::post('plans/{plan}/simulate', [SmsRoutingPlansController::class, 'simulate'])
                    ->name('simulate');

                Route::resource('rates', SmsRoutingRatesController::class)->only(['store', 'index', 'update']);
                Route::get('rates/logs', [SmsRoutingRatesController::class, 'logs'])->name('rates.logs');
            });

            Route::resource('campaigns', SmsCampaignsController::class)
                ->only(['index', 'store', 'update', 'destroy']);
//                ->parameters(['smsCampaign' => 'campaign']);

            Route::prefix('campaigns/{campaign}')->name('campaigns.')->group(function () {
                Route::resource('texts', SmsCampaignTextsController::class)->only(['index',
                    'store', 'update', 'destroy']);
                Route::resource('senderids', SmsCampaignSenderidsController::class)
                    ->only(['index',
                        'store', 'update', 'destroy']);
                Route::resource('offers', SmsCampaignOffersController::class)
                    ->only(['index', 'store', 'update', 'destroy']);

                Route::post('send-manual', [SmsCampaignsController::class, 'sendManual'])->name('send-manual');
                Route::get('logs', [SmsCampaignsController::class, 'logs'])->name('logs');
            });
        });

        Route::resource('offers', OffersController::class)->only(['index', 'store', 'update',
            'destroy']);
    });
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});