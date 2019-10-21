<?php

namespace Pace\MailTelemetry\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = [
        'hash',
        'headers',
        'sender',
        'recipient',
        'subject',
        'content',
        'opens',
        'clicks',
        'message_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'collection',
    ];

    public function urlTelemetry()
    {
        return $this->hasMany(EmailTelemetry::class);
    }
}
