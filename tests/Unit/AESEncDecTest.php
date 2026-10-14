<?php

declare(strict_types=1);

use SbiEpay\SbiEpay\Encryption\AESEncDec;
use SbiEpay\SbiEpay\Exceptions\EncryptionException;

beforeEach(function () {
    $this->encDec = new AESEncDec;
    $this->key = '1234567890123456'; // 16 bytes key
});

it('encrypts and decrypts a string successfully', function () {
    $original = '10001|DOM|IN|INR|1500.00|UDTax|https://example.com/success|https://example.com/fail|SBIEPAY|ORD99|2|NB|ONLINE|ONLINE';

    $encrypted = $this->encDec->encrypt($original, $this->key);

    expect($encrypted)->toBeString()
        ->and($encrypted)->not->toEqual($original)
        ->and(base64_decode($encrypted, true))->not->toBeFalse();

    $decrypted = $this->encDec->decrypt($encrypted, $this->key);

    expect($decrypted)->toEqual($original);
});

it('throws exception when encrypting with an empty key', function () {
    $this->encDec->encrypt('test data', '');
})->throws(EncryptionException::class);

it('throws exception when decrypting invalid base64 data', function () {
    $this->encDec->decrypt('@@@invalid-base-64@@@', $this->key);
})->throws(EncryptionException::class);

it('throws exception when decrypting with wrong key', function () {
    $encrypted = $this->encDec->encrypt('secret data', $this->key);
    $wrongKey = '9999999999999999';

    $this->encDec->decrypt($encrypted, $wrongKey);
})->throws(EncryptionException::class);
