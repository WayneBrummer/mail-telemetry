<?php

$config              = $this->app['config']->get('mail-tracker.route', []);
$config['namespace'] = 'Qit\MailTracker';

if (isNotLumen()) {
    Route::group($config, function () {
        // Route::get('t/{hash}', 'MailTrackerController@getPixel')->name('pixel_route');
        Route::get('l/{hash}/{url}', 'MailTrackerController@getLinks')->name('link_route');
        // Route::get('n', 'MailTrackerController@getClicked')->name('click_route');
    });
}
// else {
    // $app = $this->app;
    // $app->group($config, function () use ($app) {
    //     $app->get('t', 'MailTrackerController@getPixel')->name('pixel_route');
    //     $app->get('l', 'MailTrackerController@getLinks')->name('link_route');
    // });
// }

// $configAuth              = $this->app['config']->get('mail-tracker.auth-route', []);
// $configAuth['namespace'] = 'Qit\MailTracker';

//  if (Arr::get($configAuth, 'enabled', true)) {
//      if (isNotLumen()) {
//          Route::group($configAuth, function () {
//              Route::get('/', 'MailTrackerController@index')->name('email_telemetry');
//          });
//      }
//  }

// Route::post('sns', 'SNSController@callback')->name('mailTracker_SNS');
