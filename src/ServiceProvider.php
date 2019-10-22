<?php

namespace Pace\MailTelemetry;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Pace\MailTelemetry\Console\PruneCommand;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerCommands();
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->configure();
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            $this->publishPixel();
        }
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('mail-telemetry:prune')->dailyAt('17:00');
        });
        $this->registerRoutes();

        $this->app['mailer']->getSwiftMailer()->registerPlugin(new Telemetry());
    }

    public function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PruneCommand::class]);
        }
    }

    protected function publishPixel()
    {
        $this->publishes([
            __DIR__ . '/../img/1.png' => public_path('img/1.png'),
        ], 'public');
    }

    /**
     * Register the Mail Telemetry routes.
     */
    protected function registerRoutes()
    {
        $config              = $this->app['config']->get('mail-telemetry.route', []);
        $authConfig          = $this->app['config']->get('mail-telemetry.auth-route', []);
        $namespace           = ['namespace'=> 'Pace\MailTelemetry\Http\Controllers'];

        Route::group(\array_merge($config, $namespace), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });

        Route::group(\array_merge($authConfig, $namespace), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/auth.php');
        });
    }

    /**
     * Setup the configuration for Horizon.
     */
    protected function configure()
    {
        $this->publishes([
            __DIR__ . '/../config/mail-telemetry.php' => config_path('mail-telemetry.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__ . '/../config/mail-telemetry.php',
            'mail-telemetry'
        );
    }
}
