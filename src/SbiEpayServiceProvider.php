<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay;

use Illuminate\Support\ServiceProvider;
use SbiEpay\SbiEpay\Console\Commands\SbiEpayCommand;

class SbiEpayServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sbi-epay.php', 'sbi-epay');

        $this->app->singleton(SbiEpay::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/sbi-epay.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sbi-epay');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'sbi-epay');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/sbi-epay.php' => config_path('sbi-epay.php'),
        ], ['sbi-epay', 'sbi-epay-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sbi-epay'),
        ], ['sbi-epay', 'sbi-epay-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/sbi-epay'),
        ], ['sbi-epay', 'sbi-epay-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/sbi-epay'),
        ], ['sbi-epay', 'sbi-epay-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['sbi-epay', 'sbi-epay-migrations']);

        $this->commands([
            SbiEpayCommand::class,
        ]);
    }
}
