<?php

namespace Konectdigital\Mpesa;

use Illuminate\Support\ServiceProvider;
use Konectdigital\Mpesa\Console\InstallMpesaPackage;

class MpesaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // publish config file
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('mpesa.php'),
            ], 'config');

            $this->commands([
                InstallMpesaPackage::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'mpesa');

        // Register the main class to use with the facade
        $this->app->singleton('mpesa', function () {
            return new Mpesa;
        });
    }
}
