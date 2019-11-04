<?php

namespace Pace\MailTelemetry\Events;

use Illuminate\Queue\SerializesModels;
use Pace\MailTelemetry\Models\Email;

class EmailEvent
{
    use SerializesModels;

    public $email;

    public function __construct(Email $email)
    {
        $this->email = $email;
    }
}
