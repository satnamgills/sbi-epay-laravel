<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\DTOs;

use JsonSerializable;

class VerificationResponse implements JsonSerializable
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
        public string $rawResponse,
        public array $tokens = [],
        public ?string $otherDetails = null,
        public array $userDetails = [],
        public ?string $message = null,
    ) {
        if (empty($this->userDetails) && ! empty($this->otherDetails)) {
            $this->userDetails = PaymentResponse::parseUserDetails($this->otherDetails);
        }
    }

    /**
     * Parse verification response string.
     */
    public static function parse(string $responseString, string $queryOrderId, float $queryAmount): self
    {
        $raw = trim($responseString);
        $tokens = explode('|', $raw);

        // Check if response matches official SBI ePay Double Verification format (Pages 16-17):
        // atrn|status|country|currency|otherDetails|merchOrderno|amount|message...
        if (isset($tokens[5], $tokens[6]) && is_numeric($tokens[6])) {
            $sbiRef = $tokens[0] !== '' ? trim($tokens[0]) : null;
            $status = strtoupper(trim($tokens[1]));
            $otherDetails = $tokens[4] !== '' ? trim($tokens[4]) : null;
            $orderId = trim($tokens[5]);
            $amount = (float) $tokens[6];
            $message = isset($tokens[7]) && $tokens[7] !== '' ? trim($tokens[7]) : null;
        } else {
            // Legacy / alternate format: orderId|status|amount|sbiRef...
            $orderId = trim($tokens[0]);
            $status = strtoupper(trim($tokens[1] ?? 'UNKNOWN'));
            $amount = isset($tokens[2]) && is_numeric($tokens[2]) ? (float) $tokens[2] : $queryAmount;
            $sbiRef = isset($tokens[3]) && $tokens[3] !== '' ? trim($tokens[3]) : null;
            $otherDetails = null;
            $message = null;
        }

        if (str_contains(strtoupper($raw), 'SUCCESS') && $status === 'UNKNOWN') {
            $status = 'SUCCESS';
        }

        $userDetails = PaymentResponse::parseUserDetails($otherDetails);

        return new self(
            orderId: $orderId ?: $queryOrderId,
            status: $status,
            amount: $amount,
            sbiTransactionId: $sbiRef,
            rawResponse: $raw,
            tokens: $tokens,
            otherDetails: $otherDetails,
            userDetails: $userDetails,
            message: $message,
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
            'other_details' => $this->otherDetails,
            'user_details' => $this->userDetails,
            'message' => $this->message,
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
