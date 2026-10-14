<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\DTOs;

use JsonSerializable;

class VerificationResponse implements JsonSerializable
{
    /**
     * @param  array<int, string>  $tokens
     */
    public function __construct(
        public string $orderId,
        public string $status,
        public float $amount,
        public ?string $sbiTransactionId,
        public string $rawResponse,
        public array $tokens = [],
    ) {}

    /**
     * Parse verification response string.
     */
    public static function parse(string $responseString, string $queryOrderId, float $queryAmount): self
    {
        $raw = trim($responseString);
        $tokens = explode('|', $raw);

        $orderId = trim($tokens[0]);
        $status = strtoupper(trim($tokens[1] ?? 'UNKNOWN'));
        $amount = isset($tokens[2]) && is_numeric($tokens[2]) ? (float) $tokens[2] : $queryAmount;
        $sbiRef = isset($tokens[3]) && $tokens[3] !== '' ? trim($tokens[3]) : null;

        // In some formats, status might be 'SUCCESS' or 'SUCCESSFUL'
        if (str_contains(strtoupper($raw), 'SUCCESS')) {
            if ($status === 'UNKNOWN') {
                $status = 'SUCCESS';
            }
        }

        return new self(
            orderId: $orderId ?: $queryOrderId,
            status: $status,
            amount: $amount,
            sbiTransactionId: $sbiRef,
            rawResponse: $raw,
            tokens: $tokens,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'SUCCESS' || $this->status === 'SUCCESSFUL';
    }

    /**
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
            'raw_response' => $this->rawResponse,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
