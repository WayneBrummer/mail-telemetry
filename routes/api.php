<?php

use Illuminate\Support\Facades\Route;

Route::get('t/{hash}', 'MailTelemetryController@getPixel')->name('pixel_route');
Route::get('l/{hash}/{url}', 'MailTelemetryController@getLinks')->name('link_route');
Route::get('n', 'MailTelemetryController@getClicked')->name('click_route');
Route::post('sns', 'SNSController@callback')->name('sns_route');
// If lumen
// else {
    // $app = $this->app;
    // $app->group($config, function () use ($app) {
    //     $app->get('t', 'MailTelemetryController@getPixel')->name('pixel_route');
    //     $app->get('l', 'MailTelemetryController@getLinks')->name('link_route');
    // });
// }
