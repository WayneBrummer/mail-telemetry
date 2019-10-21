<?php

namespace Pace\MailTelemetry\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTelemetry extends Model
{
    protected $fillable = [
        'email_id',
        'url',
        'hash',
        'clicks',
    ];

    public function email()
    {
        $this->belongsTo(Email::class);
    }
}
