<?php

namespace Qit\MailTracker;

use Illuminate\Support\ServiceProvider;

class MailTrackerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole() && isNotLumen()) {
            $this->publishConfig();
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            $this->publishPixel();
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        }

        // $this->registerSwiftPlugin();
        $this->app['mailer']->getSwiftMailer()->registerPlugin(new MailTracker());
    }

    protected function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/mail-tracker.php' => config_path('mail-tracker.php'),
        ], 'config');
    }

    protected function publishPixel()
    {
        $this->publishes([
            __DIR__ . '/../img/1.png' => public_path('img/1.png'),
        ], 'public');
    }
}
