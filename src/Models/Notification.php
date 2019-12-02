<?php

namespace Pace\MailTelemetry\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $incrementing = false;

    protected $fillable = ['read_at'];
}
