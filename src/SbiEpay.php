<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay;

use Exception;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
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

    protected ?string $accountIdentifier;

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
        $this->accountIdentifier = $config['account_identifier'] ?? config('sbi-epay.account_identifier');
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
     * Set dynamic bank account unique identifier fluently for routing.
     */
    public function account(?string $accountIdentifier): self
    {
        $clone = clone $this;
        $clone->accountIdentifier = $accountIdentifier;

        return $clone;
    }

    public function getAccountIdentifier(): ?string
    {
        return $this->accountIdentifier;
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

        $multiAccountPayload = $request->getMultiAccountDetails($this->currency)
            ?? ($this->accountIdentifier ? sprintf('%.2f|%s|%s', $request->amount, $this->currency, $this->accountIdentifier) : null);

        $encryptedMultiAccount = null;

        if (! empty($multiAccountPayload)) {
            $encryptedMultiAccount = $this->encDec->encrypt($multiAccountPayload, $merchantKey);
        }

        return new PaymentInitiation(
            orderId: $request->orderId,
            amount: $request->amount,
            paymentUrl: $this->getPaymentUrl(),
            encryptedTrans: $encryptedTrans,
            encryptedMultiAccount: $encryptedMultiAccount,
            merchantId: $merchantId,
            accountIdentifier: $request->accountIdentifier ?: $this->accountIdentifier,
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
     * @param  PaymentResponse|string  $orderId  Order ID, PaymentResponse instance, or raw decrypted callback payload.
     * @param  float|int|string|null  $amount  Transaction amount (optional if passing PaymentResponse or decData).
     * @param  string|null  $atrn  SBI Transaction ID / ATRN (optional).
     * @param  string|null  $merchantId  Merchant ID (optional, overrides configured merchant ID).
     *
     * @throws VerificationException
     * @throws InvalidArgumentException
     */
    public function verify(
        PaymentResponse|string $orderId,
        float|int|string|null $amount = null,
        ?string $atrn = null,
        ?string $merchantId = null,
    ): VerificationResponse {
        if ($orderId instanceof PaymentResponse) {
            $amount = $amount ?? $orderId->amount;
            $atrn = $atrn ?? $orderId->sbiTransactionId;
            $merchantId = $merchantId ?? $orderId->merchantId;
            $orderId = $orderId->orderId;
        } elseif (str_contains($orderId, '|')) {
            $parsed = PaymentResponse::parse($orderId);
            $amount = $amount ?? $parsed->amount;
            $atrn = $atrn ?? $parsed->sbiTransactionId;
            $merchantId = $merchantId ?? $parsed->merchantId;
            $orderId = $parsed->orderId;
        }

        $resolvedMerchantId = ! empty($merchantId) ? (string) $merchantId : $this->getMerchantId();

        if ($amount === null) {
            throw new InvalidArgumentException('Transaction amount is required for verification.');
        }

        $amountFloat = (float) $amount;
        $amountStr = is_string($amount) && trim($amount) !== ''
            ? trim($amount)
            : number_format($amountFloat, 2, '.', '');

        $url = $this->getVerificationUrl();
        $atrnPrefix = ($atrn !== null && $atrn !== '' && $atrn !== 'NA') ? $atrn : '';
        $queryRequest = "{$atrnPrefix}|{$resolvedMerchantId}|{$orderId}|{$amountStr}";

        $params = [
            'queryRequest' => $queryRequest,
            'aggregatorId' => $this->aggregatorId,
            'merchantId' => $resolvedMerchantId,
        ];

        $body = $this->sendVerificationRequest($url, $params);

        if ($body === '') {
            throw VerificationException::emptyResponse();
        }

        return VerificationResponse::parse($body, $orderId, $amountFloat);
    }

    /**
     * Send double verification request using Http client, falling back to native cURL for HTTP/0.9 support.
     *
     * @param  array<string, string>  $params
     *
     * @throws VerificationException
     */
    protected function sendVerificationRequest(string $url, array $params): string
    {
        try {
            $response = Http::asForm()
                ->timeout(60)
                ->post($url, $params);

            if ($response->failed()) {
                throw VerificationException::requestFailed("HTTP status {$response->status()}");
            }

            $body = trim($response->body());

            if ($body !== '') {
                return $body;
            }
        } catch (Exception $e) {
            if ($e instanceof VerificationException) {
                throw $e;
            }

            if (! function_exists('curl_init')) {
                throw VerificationException::requestFailed($e->getMessage());
            }
        }

        return $this->sendViaCurl($url, $params);
    }

    /**
     * Send request via native cURL with HTTP/0.9 allowed.
     *
     * @param  array<string, string>  $params
     *
     * @throws VerificationException
     */
    protected function sendViaCurl(string $url, array $params): string
    {
        $postData = http_build_query($params);

        $ch = curl_init($url);

        if (defined('CURLOPT_HTTP09_ALLOWED')) {
            curl_setopt($ch, CURLOPT_HTTP09_ALLOWED, true);
        }
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw VerificationException::requestFailed($curlError);
        }

        return trim((string) $response);
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

        /** @var array<string, array<string, string>> $endpoints */
        $endpoints = config('sbi-epay.endpoints', [
            'test' => [
                'payment' => 'https://test.epay.sbiuat.bank.in/secure/AggregatorHostedListener',
            ],
            'live' => [
                'payment' => 'https://epay.sbi.bank.in/secure/AggregatorHostedListener',
            ],
        ]);

        return $endpoints[$this->mode]['payment'] ?? $endpoints['test']['payment'];
    }

    public function getVerificationUrl(): string
    {
        if (! empty($this->verificationUrl)) {
            return $this->verificationUrl;
        }

        /** @var array<string, array<string, string>> $endpoints */
        $endpoints = config('sbi-epay.endpoints', [
            'test' => [
                'verification' => 'https://test.epay.sbiuat.bank.in/payagg/statusQuery/getStatusQuery',
            ],
            'live' => [
                'verification' => 'https://epay.sbi.bank.in/payagg/statusQuery/getStatusQuery',
            ],
        ]);

        return $endpoints[$this->mode]['verification'] ?? $endpoints['test']['verification'];
    }
}
