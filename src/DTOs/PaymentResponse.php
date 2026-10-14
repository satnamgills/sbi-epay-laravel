<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\DTOs;

use JsonSerializable;
use SbiEpay\SbiEpay\Exceptions\InvalidCallbackException;

class PaymentResponse implements JsonSerializable
{
    /**
     * @param  array<int, string>  $tokens
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
    ) {}

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

        $orderId = trim($tokens[0]);
        $status = strtoupper(trim($tokens[1]));
        $amount = isset($tokens[3]) && is_numeric($tokens[3]) ? (float) $tokens[3] : 0.0;
        $sbiRef = isset($tokens[2]) && $tokens[2] !== '' ? trim($tokens[2]) : null;
        $bankRef = isset($tokens[8]) && $tokens[8] !== '' ? trim($tokens[8]) : null;
        $bankName = isset($tokens[9]) && $tokens[9] !== '' ? trim($tokens[9]) : null;
        $date = isset($tokens[6]) && $tokens[6] !== '' ? trim($tokens[6]) : null;
        $merchantId = isset($tokens[13]) && $tokens[13] !== '' ? trim($tokens[13]) : null;

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
        );
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
            'sbi_transaction_id' => $this->sbiTransactionId,
            'bank_reference_no' => $this->bankReferenceNo,
            'bank_name' => $this->bankName,
            'transaction_date' => $this->transactionDate,
            'merchant_id' => $this->merchantId,
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
