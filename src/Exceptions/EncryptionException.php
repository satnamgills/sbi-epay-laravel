<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\Exceptions;

class EncryptionException extends SbiEpayException
{
    public static function invalidKey(string $message = 'Merchant encryption key must not be empty.'): self
    {
        return new self($message);
    }

    public static function encryptionFailed(string $error): self
    {
        return new self("SBI ePay encryption failed: {$error}");
    }

    public static function decryptionFailed(string $error = 'Decryption failed or invalid ciphertext provided.'): self
    {
        return new self("SBI ePay decryption failed: {$error}");
    }
}
