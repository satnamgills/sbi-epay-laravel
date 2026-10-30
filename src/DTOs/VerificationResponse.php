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
        public ?string $merchantId = null,
    ) {
        if (empty($this->userDetails) && ! empty($this->otherDetails)) {
            $this->userDetails = PaymentResponse::parseUserDetails($this->otherDetails);
        }
    }

    /**
     * Parse verification response string.
     */
    public static function parse(string $responseString, string $queryOrderId, float|int|string $queryAmount = 0.0): self
    {
        $raw = trim($responseString);
        $tokens = explode('|', $raw);
        $queryAmountFloat = (float) $queryAmount;

        $clean = static fn (?string $val): ?string => ($val !== null && trim($val) !== '' && trim($val) !== 'NA') ? trim($val) : null;

        if (count($tokens) === 1) {
            $msg = $raw;
            $status = str_contains(strtoupper($raw), 'SUCCESS') ? 'SUCCESS' : 'FAILED';

            return new self(
                orderId: $queryOrderId,
                status: $status,
                amount: $queryAmountFloat,
                sbiTransactionId: null,
                rawResponse: $raw,
                tokens: $tokens,
                otherDetails: null,
                userDetails: [],
                message: $msg,
                merchantId: null,
            );
        }

        $merchantId = null;
        $orderId = $queryOrderId;
        $status = 'UNKNOWN';
        $amount = $queryAmountFloat;
        $sbiRef = null;
        $otherDetails = null;
        $message = null;

        // Check if Token 6 matches queryOrderId OR Format 2 (count >= 9):
        if ((isset($tokens[6]) && trim($tokens[6]) === $queryOrderId)
            || (count($tokens) >= 9 && ! is_numeric($tokens[2]) && (is_numeric($tokens[7]) || trim($tokens[7]) === 'NA'))) {
            // Format 2: Live SBI format: merchantId|atrn|status|country|currency|otherDetails|orderId|amount|message...
            $merchantId = $clean($tokens[0]);
            $sbiRef = $clean($tokens[1]);
            $status = strtoupper(trim($tokens[2]));
            $otherDetails = $clean($tokens[5]);
            $orderId = $clean($tokens[6]) ?: $queryOrderId;
            $amount = is_numeric($tokens[7]) ? (float) $tokens[7] : $queryAmountFloat;
            $message = $clean($tokens[8] ?? null);
        } elseif ((isset($tokens[5]) && trim($tokens[5]) === $queryOrderId)
            || (isset($tokens[5], $tokens[6]) && is_numeric($tokens[6]))) {
            // Format 1: Official manual format: atrn|status|country|currency|otherDetails|orderId|amount|message...
            $sbiRef = $clean($tokens[0]);
            $status = strtoupper(trim($tokens[1]));
            $otherDetails = $clean($tokens[4]);
            $orderId = $clean($tokens[5]) ?: $queryOrderId;
            $amount = isset($tokens[6]) && is_numeric($tokens[6]) ? (float) $tokens[6] : $queryAmountFloat;
            $message = $clean($tokens[7] ?? null);
        } else {
            // Format 4: Legacy / alternate format: orderId|status|amount|sbiRef...
            $orderId = $clean($tokens[0]) ?: $queryOrderId;
            $status = strtoupper(trim($tokens[1] ?? 'UNKNOWN'));
            $amount = isset($tokens[2]) && is_numeric($tokens[2]) ? (float) $tokens[2] : $queryAmountFloat;
            $sbiRef = $clean($tokens[3] ?? null);
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
            merchantId: $merchantId,
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
            'merchant_id' => $this->merchantId,
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
