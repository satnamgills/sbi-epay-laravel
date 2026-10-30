<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\DTOs;

class PaymentRequest
{
    /**
     * @param  array<string, mixed>  $userDetails
     */
    public function __construct(
        public string $orderId,
        public float $amount,
        public string $successUrl,
        public string $failUrl,
        public string $currency = 'INR',
        public string $country = 'IN',
        public ?string $otherDetails = 'Other',
        public ?string $multiAccountDetails = null,
        public ?string $operatingMode = null,
        public ?string $aggregatorId = null,
        public ?string $customerEmail = null,
        public ?string $customerMobile = null,
        public ?string $accountIdentifier = null,
        public array $userDetails = [],
        public ?string $customerId = null,
    ) {
        if (! empty($this->userDetails)) {
            $this->otherDetails = self::formatUserDetails($this->userDetails);
        }
    }

    /**
     * Create an instance from an associative array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $userDetails = [];

        if (isset($data['user_details']) && is_array($data['user_details'])) {
            $userDetails = $data['user_details'];
        } elseif (isset($data['userDetails']) && is_array($data['userDetails'])) {
            $userDetails = $data['userDetails'];
        } elseif (isset($data['other_details']) && is_array($data['other_details'])) {
            $userDetails = $data['other_details'];
        } elseif (isset($data['otherDetails']) && is_array($data['otherDetails'])) {
            $userDetails = $data['otherDetails'];
        }

        $otherDetails = null;

        if (! empty($userDetails)) {
            $otherDetails = self::formatUserDetails($userDetails);
        } elseif (isset($data['other_details']) || isset($data['otherDetails'])) {
            $rawOther = $data['other_details'] ?? $data['otherDetails'];
            $otherDetails = is_string($rawOther) ? self::sanitizePacketValue($rawOther) : 'Other';
        } else {
            $otherDetails = 'Other';
        }

        $accountIdentifier = ($val = $data['account_identifier'] ?? $data['accountIdentifier'] ?? $data['bank_account_id'] ?? null) !== null
            ? (string) $val
            : null;

        $customerId = ($val = $data['customer_id'] ?? $data['customerId'] ?? $data['merchant_customer_id'] ?? null) !== null
            ? (string) $val
            : null;

        return new self(
            orderId: (string) ($data['order_id'] ?? $data['orderId']),
            amount: (float) $data['amount'],
            successUrl: (string) ($data['success_url'] ?? $data['successUrl']),
            failUrl: (string) ($data['fail_url'] ?? $data['failUrl']),
            currency: (string) ($data['currency'] ?? 'INR'),
            country: (string) ($data['country'] ?? 'IN'),
            otherDetails: $otherDetails,
            multiAccountDetails: ($val = $data['multi_account_details'] ?? $data['multiAccountDetails'] ?? null) !== null ? (string) $val : null,
            operatingMode: ($val = $data['operating_mode'] ?? $data['operatingMode'] ?? null) !== null ? (string) $val : null,
            aggregatorId: ($val = $data['aggregator_id'] ?? $data['aggregatorId'] ?? null) !== null ? (string) $val : null,
            customerEmail: ($val = $data['customer_email'] ?? $data['customerEmail'] ?? null) !== null ? (string) $val : null,
            customerMobile: ($val = $data['customer_mobile'] ?? $data['customerMobile'] ?? null) !== null ? (string) $val : null,
            accountIdentifier: $accountIdentifier,
            userDetails: $userDetails,
            customerId: $customerId,
        );
    }

    /**
     * Get or build multi-account instruction details for bank account routing.
     */
    public function getMultiAccountDetails(?string $defaultCurrency = 'INR'): ?string
    {
        if (! empty($this->multiAccountDetails)) {
            return $this->multiAccountDetails;
        }

        if (! empty($this->accountIdentifier)) {
            $currency = $this->currency ?: ($defaultCurrency ?: 'INR');
            $amountFormatted = number_format($this->amount, 2, '.', '');

            return sprintf('%s|%s|%s', $amountFormatted, $currency, $this->accountIdentifier);
        }

        return null;
    }

    /**
     * Format arbitrary key-value array into a compliant SBI ePay OtherDetails string.
     *
     * Permitted characters in SBI ePay data packets: alphanumeric, @, /, _, -, space, ., :
     * Delimiters '|' and '=' are strictly forbidden inside payload data.
     *
     * @param  array<string, mixed>  $userDetails
     */
    public static function formatUserDetails(array $userDetails): string
    {
        if (empty($userDetails)) {
            return 'Other';
        }

        $pairs = [];
        foreach ($userDetails as $key => $value) {
            $cleanKey = self::sanitizePacketValue((string) $key);
            $cleanValue = self::sanitizePacketValue(is_scalar($value) ? (string) $value : (string) json_encode($value));

            if ($cleanKey !== '') {
                $pairs[] = "{$cleanKey}:{$cleanValue}";
            }
        }

        if (empty($pairs)) {
            return 'Other';
        }

        $formatted = implode('/', $pairs);

        return mb_substr($formatted, 0, 1000);
    }

    /**
     * Sanitize a value according to SBI ePay packet special character guidelines.
     */
    public static function sanitizePacketValue(string $value): string
    {
        $sanitized = str_replace(['|', '='], ['-', ':'], $value);
        $sanitized = str_replace(["\r", "\n"], '', $sanitized);

        return trim($sanitized);
    }

    /**
     * Build the raw pipe-separated parameter string expected by SBI ePay.
     */
    public function toPipePayload(
        string $merchantId,
        string $defaultAggregatorId = 'SBIEPAY',
        string $defaultOperatingMode = '2',
        string $defaultCountry = 'IN',
        string $defaultCurrency = 'INR',
    ): string {
        $aggregator = $this->aggregatorId ?: $defaultAggregatorId;
        $operatingMode = $this->operatingMode ?: $defaultOperatingMode;
        $country = $this->country ?: $defaultCountry;
        $currency = $this->currency ?: $defaultCurrency;
        $other = $this->otherDetails ?: 'Other';
        $amountFormatted = number_format($this->amount, 2, '.', '');
        $customerId = $this->customerId ?: $operatingMode;

        // Format: MerchantId|DOM|Country|Currency|Amount|Other|SuccessUrl|FailUrl|AggregatorId|OrderId|CustomerId/OperatingMode|NB|ONLINE|ONLINE
        return sprintf(
            '%s|DOM|%s|%s|%s|%s|%s|%s|%s|%s|%s|NB|ONLINE|ONLINE',
            $merchantId,
            $country,
            $currency,
            $amountFormatted,
            $other,
            $this->successUrl,
            $this->failUrl,
            $aggregator,
            $this->orderId,
            $customerId,
        );
    }
}
