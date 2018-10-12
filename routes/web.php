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

Route::get('/grab_marketing_stat/{params}', 'grabMarketingStat@grab');
Route::get('/grab_marketing_stat_bing', 'grabMarketingStat@grabBing');
Route::get('/grab_marketing_stat_linkedin', 'grabMarketingStat@grabLinkedin');
Route::get('/grab_google_analytics_reports', 'GrabDimensionsFromAnalyticsController@grab');

Route::get('/ga_reports/', 'GoogleAnalyticsReportsController@index');
Route::get('/ga_report', 'GoogleAnalyticsReportsController@show_add_form');
Route::post('/ga_report', 'GoogleAnalyticsReportsController@add');
Route::get('/ga_report/{id}/edit', 'GoogleAnalyticsReportsController@show_for_edit');
Route::post('/ga_report/{id}', 'GoogleAnalyticsReportsController@edit');
Route::delete('/ga_report/{id}', 'GoogleAnalyticsReportsController@delete');
Route::post('/ga_reports', 'GoogleAnalyticsReportsController@get');

Route::get('/ga_reports/schedules', 'GoogleAnalyticsReportsScheduleController@index');
Route::get('/ga_reports/schedule', 'GoogleAnalyticsReportsScheduleController@show_add_form');
Route::post('/ga_reports/schedule', 'GoogleAnalyticsReportsScheduleController@add');
Route::get('/ga_reports/schedule/{id}/edit', 'GoogleAnalyticsReportsScheduleController@show_for_edit');
Route::post('/ga_reports/schedule/{id}', 'GoogleAnalyticsReportsScheduleController@edit');
Route::delete('/ga_reports/schedule/{id}', 'GoogleAnalyticsReportsScheduleController@delete');

Route::get('/ga_reports_posts',
    ['uses' => 'GoogleAnalyticsReportsController@show_posts', 'as' => 'ga_reports.posts.form_data']);

Route::get('/ga_reports_clusters', 'GoogleAnalyticsReportsClustersController@index');
Route::get('/ga_reports_clusters/{id}/edit', 'GoogleAnalyticsReportsClustersController@show_for_edit');
Route::post('/ga_reports_clusters/{id}', 'GoogleAnalyticsReportsClustersController@edit');
Route::post('/ga_reports_clusters', 'GoogleAnalyticsReportsClustersController@add');
Route::get('/ga_reports_cluster', 'GoogleAnalyticsReportsClustersController@show_add_form');
Route::delete('/ga_reports_clusters/{id}', 'GoogleAnalyticsReportsClustersController@delete');

Route::post('/ga_reports_posts/change_post_cluster/{id}', 'GoogleAnalyticsReportsPostsController@change_post_cluster');
Route::get('/ga_reports_posts/grab_posts', 'GoogleAnalyticsReportsPostsController@grab_posts');

Route::get('/marketing_stat_arbitary', 'grabMarketingStat@index');
Route::post('/marketing_stat_arbitary', 'grabMarketingStat@get');

Route::get('/bing', 'grabMarketingStat@grabBing');
Route::get('/analytics', 'grabMarketingStat@result');
Route::get('/title_checker', 'TitleCheckerController@index');