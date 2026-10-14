<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\DTOs;

class PaymentRequest
{
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
    ) {}

    /**
     * Create an instance from an associative array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId: (string) ($data['order_id'] ?? $data['orderId']),
            amount: (float) $data['amount'],
            successUrl: (string) ($data['success_url'] ?? $data['successUrl']),
            failUrl: (string) ($data['fail_url'] ?? $data['failUrl']),
            currency: (string) ($data['currency'] ?? 'INR'),
            country: (string) ($data['country'] ?? 'IN'),
            otherDetails: isset($data['other_details']) || isset($data['otherDetails'])
                ? (string) ($data['other_details'] ?? $data['otherDetails'])
                : 'Other',
            multiAccountDetails: $data['multi_account_details'] ?? $data['multiAccountDetails'] ?? null,
            operatingMode: $data['operating_mode'] ?? $data['operatingMode'] ?? null,
            aggregatorId: $data['aggregator_id'] ?? $data['aggregatorId'] ?? null,
            customerEmail: $data['customer_email'] ?? $data['customerEmail'] ?? null,
            customerMobile: $data['customer_mobile'] ?? $data['customerMobile'] ?? null,
        );
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

        // Format: MerchantId|DOM|Country|Currency|Amount|Other|SuccessUrl|FailUrl|AggregatorId|OrderId|OperatingMode|NB|ONLINE|ONLINE
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
            $operatingMode,
        );
    }
}
