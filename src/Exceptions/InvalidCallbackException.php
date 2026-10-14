<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\Exceptions;

class InvalidCallbackException extends SbiEpayException
{
    public static function missingEncData(): self
    {
        return new self('Missing "encData" in SBI ePay callback request.');
    }

    public static function malformedPayload(string $raw): self
    {
        return new self("Malformed SBI ePay response payload: {$raw}");
    }
}
