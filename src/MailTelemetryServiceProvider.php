<?php

namespace Pace\MailTelemetry;

use Illuminate\Support\ServiceProvider;

class MailTelemetryServiceProvider extends ServiceProvider
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
        $this->app['mailer']->getSwiftMailer()->registerPlugin(new MailTelemetry());
    }

    protected function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/mail-telemetry.php' => config_path('mail-telemetry.php'),
        ], 'config');
    }

    protected function publishPixel()
    {
        $this->publishes([
            __DIR__ . '/../img/1.png' => public_path('img/1.png'),
        ], 'public');
    }
}
