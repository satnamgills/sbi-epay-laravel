<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\Exceptions;

class VerificationException extends SbiEpayException
{
    public static function requestFailed(string $error): self
    {
        return new self("SBI ePay double verification request failed: {$error}");
    }

    public static function emptyResponse(): self
    {
        return new self('Empty response received from SBI ePay double verification service.');
    }
}
