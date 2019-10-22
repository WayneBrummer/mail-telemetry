<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'MailTelemetryController@index')->name('email_telemetry');
