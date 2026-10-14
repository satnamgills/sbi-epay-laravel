<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay;

use Exception;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SbiEpay\SbiEpay\DTOs\PaymentInitiation;
use SbiEpay\SbiEpay\DTOs\PaymentRequest;
use SbiEpay\SbiEpay\DTOs\PaymentResponse;
use SbiEpay\SbiEpay\DTOs\VerificationResponse;
use SbiEpay\SbiEpay\Encryption\AESEncDec;
use SbiEpay\SbiEpay\Exceptions\InvalidCallbackException;
use SbiEpay\SbiEpay\Exceptions\VerificationException;

class SbiEpay
{
    protected AESEncDec $encDec;

    protected string $mode;

    protected ?string $merchantId;

    protected ?string $merchantKey;

    protected ?string $paymentUrl;

    protected ?string $verificationUrl;

    protected string $aggregatorId;

    protected string $operatingMode;

    protected string $country;

    protected string $currency;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->encDec = new AESEncDec;
        $this->mode = (string) ($config['mode'] ?? config('sbi-epay.mode', 'test'));
        $this->merchantId = $config['merchant_id'] ?? config('sbi-epay.merchant_id');
        $this->merchantKey = $config['merchant_key'] ?? config('sbi-epay.merchant_key');
        $this->paymentUrl = $config['payment_url'] ?? config('sbi-epay.payment_url');
        $this->verificationUrl = $config['verification_url'] ?? config('sbi-epay.verification_url');
        $this->aggregatorId = (string) ($config['aggregator_id'] ?? config('sbi-epay.aggregator_id', 'SBIEPAY'));
        $this->operatingMode = (string) ($config['operating_mode'] ?? config('sbi-epay.operating_mode', '2'));
        $this->country = (string) ($config['country'] ?? config('sbi-epay.country', 'IN'));
        $this->currency = (string) ($config['currency'] ?? config('sbi-epay.currency', 'INR'));
    }

    /**
     * Set dynamic merchant credentials fluently.
     */
    public function merchant(string $merchantId, string $merchantKey): self
    {
        $clone = clone $this;
        $clone->merchantId = $merchantId;
        $clone->merchantKey = $merchantKey;

        return $clone;
    }

    /**
     * Set the gateway environment mode ('test' or 'live').
     */
    public function mode(string $mode): self
    {
        $clone = clone $this;
        $clone->mode = $mode;

        return $clone;
    }

    /**
     * Initiate a payment request and return PaymentInitiation object.
     *
     * @param  PaymentRequest|array<string, mixed>  $request
     */
    public function initiate(PaymentRequest|array $request): PaymentInitiation
    {
        if (is_array($request)) {
            $request = PaymentRequest::fromArray($request);
        }

        $merchantId = $this->getMerchantId();
        $merchantKey = $this->getMerchantKey();

        $pipePayload = $request->toPipePayload(
            merchantId: $merchantId,
            defaultAggregatorId: $this->aggregatorId,
            defaultOperatingMode: $this->operatingMode,
            defaultCountry: $this->country,
            defaultCurrency: $this->currency,
        );

        $encryptedTrans = $this->encDec->encrypt($pipePayload, $merchantKey);

        $encryptedMultiAccount = null;

        if (! empty($request->multiAccountDetails)) {
            $encryptedMultiAccount = $this->encDec->encrypt($request->multiAccountDetails, $merchantKey);
        }

        return new PaymentInitiation(
            orderId: $request->orderId,
            amount: $request->amount,
            paymentUrl: $this->getPaymentUrl(),
            encryptedTrans: $encryptedTrans,
            encryptedMultiAccount: $encryptedMultiAccount,
            merchantId: $merchantId,
        );
    }

    /**
     * Decrypt and parse the callback payload sent by SBI ePay.
     *
     * @throws InvalidCallbackException
     */
    public function handleCallback(?string $encData): PaymentResponse
    {
        if (empty($encData)) {
            throw InvalidCallbackException::missingEncData();
        }

        $merchantKey = $this->getMerchantKey();
        $decrypted = $this->encDec->decrypt($encData, $merchantKey);

        return PaymentResponse::parse($decrypted);
    }

    /**
     * Perform Double Verification with SBI ePay server.
     *
     * @throws VerificationException
     */
    public function verify(string $orderId, float|int|string $amount): VerificationResponse
    {
        $merchantId = $this->getMerchantId();
        $amountFloat = (float) $amount;
        $amountFormatted = number_format($amountFloat, 2, '.', '');

        $url = $this->getVerificationUrl();
        $queryRequest = "|{$merchantId}|{$orderId}|{$amountFormatted}";

        try {
            $response = Http::asForm()
                ->timeout(60)
                ->post($url, [
                    'queryRequest' => $queryRequest,
                    'aggregatorId' => $this->aggregatorId,
                    'merchantId' => $merchantId,
                ]);

            if ($response->failed()) {
                throw VerificationException::requestFailed("HTTP status {$response->status()}");
            }

            $body = trim($response->body());

            if ($body === '') {
                throw VerificationException::emptyResponse();
            }

            return VerificationResponse::parse($body, $orderId, $amountFloat);
        } catch (Exception $e) {
            if ($e instanceof VerificationException) {
                throw $e;
            }

            throw VerificationException::requestFailed($e->getMessage());
        }
    }

    /**
     * Encrypt a string using the current merchant key.
     */
    public function encrypt(string $plainText, ?string $key = null): string
    {
        return $this->encDec->encrypt($plainText, $key ?: $this->getMerchantKey());
    }

    /**
     * Decrypt a ciphertext using the current merchant key.
     */
    public function decrypt(string $cipherText, ?string $key = null): string
    {
        return $this->encDec->decrypt($cipherText, $key ?: $this->getMerchantKey());
    }

    public function getMerchantId(): string
    {
        if (empty($this->merchantId)) {
            throw new RuntimeException('SBI ePay merchant_id is not configured.');
        }

        return (string) $this->merchantId;
    }

    public function getMerchantKey(): string
    {
        if (empty($this->merchantKey)) {
            throw new RuntimeException('SBI ePay merchant_key is not configured.');
        }

        return (string) $this->merchantKey;
    }

    public function getPaymentUrl(): string
    {
        if (! empty($this->paymentUrl)) {
            return $this->paymentUrl;
        }

        $endpoints = config('sbi-epay.endpoints', [
            'test' => [
                'payment' => 'https://test.sbiepay.sbi/secure/AggregatorHostedListener',
            ],
            'live' => [
                'payment' => 'https://www.sbiepay.sbi/secure/AggregatorHostedListener',
            ],
        ]);

        return $endpoints[$this->mode]['payment'] ?? $endpoints['test']['payment'];
    }

    public function getVerificationUrl(): string
    {
        if (! empty($this->verificationUrl)) {
            return $this->verificationUrl;
        }

        $endpoints = config('sbi-epay.endpoints', [
            'test' => [
                'verification' => 'https://test.sbiepay.sbi/secure/DoubleVerificationGateway',
            ],
            'live' => [
                'verification' => 'https://www.sbiepay.sbi/secure/DoubleVerificationGateway',
            ],
        ]);

        return $endpoints[$this->mode]['verification'] ?? $endpoints['test']['verification'];
    }
}
