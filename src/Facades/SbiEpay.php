<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SbiEpay\SbiEpay\DTOs\PaymentInitiation initiate(\SbiEpay\SbiEpay\DTOs\PaymentRequest|array<string, mixed> $request)
 * @method static \SbiEpay\SbiEpay\DTOs\PaymentResponse handleCallback(?string $encData)
 * @method static \SbiEpay\SbiEpay\DTOs\VerificationResponse verify(\SbiEpay\SbiEpay\DTOs\PaymentResponse|string $orderId, float|int|string|null $amount = null, ?string $atrn = null, ?string $merchantId = null)
 * @method static \SbiEpay\SbiEpay\SbiEpay merchant(string $merchantId, string $merchantKey)
 * @method static \SbiEpay\SbiEpay\SbiEpay account(?string $accountIdentifier)
 * @method static ?string getAccountIdentifier()
 * @method static \SbiEpay\SbiEpay\SbiEpay mode(string $mode)
 * @method static string encrypt(string $plainText, ?string $key = null)
 * @method static string decrypt(string $cipherText, ?string $key = null)
 * @method static string getPaymentUrl()
 * @method static string getVerificationUrl()
 *
 * @see \SbiEpay\SbiEpay\SbiEpay
 */
class SbiEpay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SbiEpay\SbiEpay\SbiEpay::class;
    }
}
