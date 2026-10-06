<?php

declare(strict_types=1);

use SbiEpay\SbiEpay\SbiEpay;

it('resolves the singleton', function () {
    expect(app(SbiEpay::class))->toBeInstanceOf(SbiEpay::class);
});

it('returns the same instance from the container', function () {
    expect(app(SbiEpay::class))->toBe(app(SbiEpay::class));
});

it('merges the package config', function () {
    expect(config('sbi-epay.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('sbi-epay::messages.placeholder'))->toBe('SbiEpay placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('sbi-epay::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('sbi-epay:placeholder')
        ->expectsOutputToContain('SbiEpay placeholder command executed.')
        ->assertSuccessful();
});
