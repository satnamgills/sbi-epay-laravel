<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\DTOs;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use JsonSerializable;

class PaymentInitiation implements JsonSerializable
{
    public function __construct(
        public string $orderId,
        public float $amount,
        public string $paymentUrl,
        public string $encryptedTrans,
        public ?string $encryptedMultiAccount,
        public string $merchantId,
        public ?string $accountIdentifier = null,
    ) {}

    /**
     * Render the auto-submitting POST redirect view.
     */
    public function redirect(): Response
    {
        /** @var view-string $view */
        $view = 'sbi-epay::redirect';

        return response()->view($view, [
            'initiation' => $this,
            'url' => $this->paymentUrl,
            'encryptedTrans' => $this->encryptedTrans,
            'multiAccount' => $this->encryptedMultiAccount ?? '',
            'merchantId' => $this->merchantId,
            'orderId' => $this->orderId,
            'amount' => $this->amount,
        ]);
    }

    /**
     * Get the View instance.
     */
    public function view(): View
    {
        /** @var view-string $view */
        $view = 'sbi-epay::redirect';

        return \Illuminate\Support\Facades\View::make($view, [
            'initiation' => $this,
            'url' => $this->paymentUrl,
            'encryptedTrans' => $this->encryptedTrans,
            'multiAccount' => $this->encryptedMultiAccount ?? '',
            'merchantId' => $this->merchantId,
            'orderId' => $this->orderId,
            'amount' => $this->amount,
        ]);
    }

    /**
     * Get associative array of form data for custom implementations.
     *
     * @return array<string, string>
     */
    public function formData(): array
    {
        return [
            'action' => $this->paymentUrl,
            'EncryptTrans' => $this->encryptedTrans,
            'MultiAccountInstructionDtls' => $this->encryptedMultiAccount ?? '',
            'merchIdVal' => $this->merchantId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'payment_url' => $this->paymentUrl,
            'encrypted_trans' => $this->encryptedTrans,
            'encrypted_multi_account' => $this->encryptedMultiAccount,
            'merchant_id' => $this->merchantId,
            'account_identifier' => $this->accountIdentifier,
            'form_data' => $this->formData(),
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
