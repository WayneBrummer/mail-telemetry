<?php

namespace Pace\MailTelemetry\Console;

use Illuminate\Console\Command;
use Pace\MailTelemetry\Models\Email;
use Pace\MailTelemetry\Models\EmailTelemetry;

class PruneCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'mail-telemetry:prune {days?}';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Prune telemetry items in a email collection.';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        if (config('mail-telemetry.expire-days') > 0 || $this->argument('days') > 0) {
            $days   = now()->subDays($this->argument('days') ?? config('mail-telemetry.expire-days'));
            $emails = Email::where('created_at', '<', $days)
                ->select('id')
                ->get();

            EmailTelemetry::whereIn('sent_email_id', $emails->pluck('id'))->delete();
            $this->info(Email::whereIn('id', $emails->pluck('id'))->delete() . ' entries pruned.');
        }
    }
}
