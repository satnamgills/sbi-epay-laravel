<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use SbiEpay\SbiEpay\SbiEpay;
use SbiEpay\SbiEpay\SbiEpayServiceProvider;

it('resolves the singleton', function () {
    expect(app(SbiEpay::class))->toBeInstanceOf(SbiEpay::class);
});

it('returns the same instance from the container', function () {
    expect(app(SbiEpay::class))->toBe(app(SbiEpay::class));
});

it('merges the package config', function () {
    expect(config('sbi-epay.mode'))->toBe('test');
});

it('loads the package views', function () {
    expect(view()->exists('sbi-epay::redirect'))->toBeTrue();
});

it('registers the publishable config', function () {
    $paths = ServiceProvider::pathsToPublish(SbiEpayServiceProvider::class, 'sbi-epay-config');

    expect($paths)->not->toBeEmpty()
        ->and(array_values($paths)[0])->toBe(config_path('sbi-epay.php'));
});
