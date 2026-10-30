<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\DTOs;

use JsonSerializable;
use SbiEpay\SbiEpay\Exceptions\InvalidCallbackException;

class PaymentResponse implements JsonSerializable
{
    /**
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $userDetails
     */
    public function __construct(
        public string $orderId,
        public string $status,
        public float $amount,
        public ?string $sbiTransactionId,
        public ?string $bankReferenceNo,
        public ?string $bankName,
        public ?string $transactionDate,
        public ?string $merchantId,
        public string $rawResponse,
        public array $tokens = [],
        public ?string $otherDetails = null,
        public array $userDetails = [],
        public ?string $currency = null,
        public ?string $paymode = null,
        public ?string $message = null,
        public ?string $cin = null,
    ) {
        if (empty($this->userDetails) && ! empty($this->otherDetails)) {
            $this->userDetails = self::parseUserDetails($this->otherDetails);
        }
    }

    /**
     * Parse decrypted SBI ePay pipe-delimited callback string.
     *
     * @throws InvalidCallbackException
     */
    public static function parse(string $decryptedPayload): self
    {
        $raw = trim($decryptedPayload);

        if ($raw === '') {
            throw InvalidCallbackException::malformedPayload('Decrypted response is empty.');
        }

        $tokens = explode('|', $raw);

        if (count($tokens) < 2) {
            throw InvalidCallbackException::malformedPayload($raw);
        }

        $isLegacyFormat = in_array(
            strtoupper(trim($tokens[1])),
            ['SUCCESS', 'FAIL', 'FAILED', 'FAILURE', 'PENDING', 'IN_PROCESS', 'AWAITED'],
            true,
        );

        if ($isLegacyFormat) {
            $orderId = trim($tokens[0]);
            $status = strtoupper(trim($tokens[1]));
            $sbiRef = isset($tokens[2]) && $tokens[2] !== '' ? trim($tokens[2]) : null;
            $amount = isset($tokens[3]) && is_numeric($tokens[3]) ? (float) $tokens[3] : 0.0;
            $currency = isset($tokens[4]) && $tokens[4] !== '' ? trim($tokens[4]) : null;
            $otherDetails = isset($tokens[5]) && $tokens[5] !== '' ? trim($tokens[5]) : null;
            $date = isset($tokens[6]) && $tokens[6] !== '' ? trim($tokens[6]) : null;
            $bankRef = isset($tokens[8]) && $tokens[8] !== '' ? trim($tokens[8]) : null;
            $bankName = isset($tokens[9]) && $tokens[9] !== '' ? trim($tokens[9]) : null;
            $paymode = isset($tokens[11]) && $tokens[11] !== '' ? trim($tokens[11]) : null;
            $merchantId = isset($tokens[13]) && $tokens[13] !== '' ? trim($tokens[13]) : null;
            $message = null;
            $cin = null;
        } else {
            // Official SBI ePay manual format (Pages 9-10)
            // Format: orderReqId|atrn|transStatus|amount|currency|paymode|otherDetails|message|bankCode|bankRefNumber|trascationdate|Country|CIN...
            $orderId = trim($tokens[0]);
            $sbiRef = $tokens[1] !== '' ? trim($tokens[1]) : null;
            $status = strtoupper(trim($tokens[2] ?? 'UNKNOWN'));
            $amount = isset($tokens[3]) && is_numeric($tokens[3]) ? (float) $tokens[3] : 0.0;
            $currency = isset($tokens[4]) && $tokens[4] !== '' ? trim($tokens[4]) : null;
            $paymode = isset($tokens[5]) && $tokens[5] !== '' ? trim($tokens[5]) : null;
            $otherDetails = isset($tokens[6]) && $tokens[6] !== '' ? trim($tokens[6]) : null;
            $message = isset($tokens[7]) && $tokens[7] !== '' ? trim($tokens[7]) : null;
            $bankName = isset($tokens[8]) && $tokens[8] !== '' ? trim($tokens[8]) : null;
            $bankRef = isset($tokens[9]) && $tokens[9] !== '' ? trim($tokens[9]) : null;
            $date = isset($tokens[10]) && $tokens[10] !== '' ? trim($tokens[10]) : null;
            $cin = isset($tokens[12]) && $tokens[12] !== '' && $tokens[12] !== 'NA' ? trim($tokens[12]) : null;
            $merchantId = null;
        }

        $userDetails = self::parseUserDetails($otherDetails);

        return new self(
            orderId: $orderId,
            status: $status,
            amount: $amount,
            sbiTransactionId: $sbiRef,
            bankReferenceNo: $bankRef,
            bankName: $bankName,
            transactionDate: $date,
            merchantId: $merchantId,
            rawResponse: $raw,
            tokens: $tokens,
            otherDetails: $otherDetails,
            userDetails: $userDetails,
            currency: $currency,
            paymode: $paymode,
            message: $message,
            cin: $cin,
        );
    }

    /**
     * Parse arbitrary key-value user details from OtherDetails string.
     *
     * @return array<string, string>
     */
    public static function parseUserDetails(?string $otherDetails): array
    {
        if (empty($otherDetails) || in_array(strtoupper($otherDetails), ['OTHER', 'NULL', 'NA'], true)) {
            return [];
        }

        $trimmed = trim($otherDetails);

        if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
            $decoded = json_decode($trimmed, true);

            if (is_array($decoded)) {
                /** @var array<string, string> $decoded */
                return $decoded;
            }
        }

        $delimiter = '/';

        if (str_contains($trimmed, '&') && ! str_contains($trimmed, '/')) {
            $delimiter = '&';
        }

        $pairs = explode($delimiter, $trimmed);
        $result = [];

        foreach ($pairs as $pair) {
            $pair = trim($pair);

            if ($pair === '') {
                continue;
            }

            if (str_contains($pair, ':')) {
                [$key, $val] = explode(':', $pair, 2);
                $result[trim($key)] = trim($val);
            } elseif (str_contains($pair, '=')) {
                [$key, $val] = explode('=', $pair, 2);
                $result[trim($key)] = trim($val);
            }
        }

        return $result;
    }

    /**
     * Check if transaction succeeded.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'SUCCESS';
    }

    /**
     * Check if transaction failed.
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['FAIL', 'FAILED', 'FAILURE'], true);
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['PENDING', 'IN_PROCESS', 'AWAITED'], true);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'status' => $this->status,
            'is_success' => $this->isSuccess(),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'paymode' => $this->paymode,
            'message' => $this->message,
            'sbi_transaction_id' => $this->sbiTransactionId,
            'bank_reference_no' => $this->bankReferenceNo,
            'bank_name' => $this->bankName,
            'transaction_date' => $this->transactionDate,
            'merchant_id' => $this->merchantId,
            'cin' => $this->cin,
            'other_details' => $this->otherDetails,
            'user_details' => $this->userDetails,
            'raw_response' => $this->rawResponse,
        ];
    }

    /**
     * JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
