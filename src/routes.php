<?php

$config              = $this->app['config']->get('mail-telemetry.route', []);
$config['namespace'] = 'Pace\MailTelemetry';

if (isNotLumen()) {
    Route::group($config, function () {
        // Route::get('t/{hash}', 'MailTelemetryController@getPixel')->name('pixel_route');
        Route::get('l/{hash}/{url}', 'MailTelemetryController@getLinks')->name('link_route');
        // Route::get('n', 'MailTelemetryController@getClicked')->name('click_route');
    });
}
// else {
    // $app = $this->app;
    // $app->group($config, function () use ($app) {
    //     $app->get('t', 'MailTelemetryController@getPixel')->name('pixel_route');
    //     $app->get('l', 'MailTelemetryController@getLinks')->name('link_route');
    // });
// }

// $configAuth              = $this->app['config']->get('mail-telemetry.auth-route', []);
// $configAuth['namespace'] = 'Pace\MailTelemetry';

//  if (Arr::get($configAuth, 'enabled', true)) {
//      if (isNotLumen()) {
//          Route::group($configAuth, function () {
//              Route::get('/', 'MailTelemetryController@index')->name('email_telemetry');
//          });
//      }
//  }

// Route::post('sns', 'SNSController@callback')->name('sns_route');
