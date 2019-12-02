<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'MailController@index')->name('email_index');
Route::get('/{id}', 'MailController@show')->name('email_telemetry');
