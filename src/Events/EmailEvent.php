<?php

namespace Pace\MailTelemetry\Events;

use Illuminate\Queue\SerializesModels;

class EmailEvent
{
    use SerializesModels;

    public $email;

    public function __construct($email)
    {
        $this->email = $email;
    }
}
