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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/grab_marketing_stat', 'grabMarketingStat@grab');
Route::get('/grab_marketing_stat_bing', 'grabMarketingStat@grabBing');
Route::get('/grab_marketing_stat_linkedin', 'grabMarketingStat@grabLinkedin');
Route::get('/grab_google_analytics_reports', 'GrabDimensionsFromAnalyticsController@grab');
Route::get('/example_sheets', 'grabMarketingStat@googleSheets');