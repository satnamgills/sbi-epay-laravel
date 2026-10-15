<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class SbiEpayServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sbi-epay.php', 'sbi-epay');

        $this->app->singleton(SbiEpay::class, function (Container $app): SbiEpay {
            /** @var Repository $configRepo */
            $configRepo = $app->make('config');
            /** @var array<string, mixed> $config */
            $config = $configRepo->get('sbi-epay', []);

            return new SbiEpay($config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sbi-epay');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/sbi-epay.php' => config_path('sbi-epay.php'),
        ], ['sbi-epay', 'sbi-epay-config']);
    }
}
