# SBI ePay for Laravel

<p align="center">
    <a href="https://packagist.org/packages/satnam-singh/sbi-epay"><img src="https://img.shields.io/packagist/v/satnam-singh/sbi-epay.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://packagist.org/packages/satnam-singh/sbi-epay"><img src="https://img.shields.io/packagist/php-v/satnam-singh/sbi-epay.svg?style=flat-square" alt="PHP Version Support"></a>
    <a href="https://github.com/satnamgills/sbi-epay-laravel/actions/workflows/tests.yml"><img src="https://github.com/satnamgills/sbi-epay-laravel/actions/workflows/tests.yml/badge.svg?branch=main" alt="Tests Status"></a>
    <a href="https://github.com/satnamgills/sbi-epay-laravel/actions/workflows/tag-and-release.yml"><img src="https://github.com/satnamgills/sbi-epay-laravel/actions/workflows/tag-and-release.yml/badge.svg?branch=main" alt="Release Workflow Status"></a>
    <a href="https://packagist.org/packages/satnam-singh/sbi-epay"><img src="https://img.shields.io/packagist/dt/satnam-singh/sbi-epay.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://github.com/satnamgills/sbi-epay-laravel"><img src="https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat-square" alt="PHPStan Level 7"></a>
    <a href="https://github.com/satnamgills/sbi-epay-laravel"><img src="https://img.shields.io/badge/type--coverage-100%25-brightgreen.svg?style=flat-square" alt="Type Coverage 100%"></a>
    <a href="https://github.com/satnamgills/sbi-epay-laravel/network/updates"><img src="https://img.shields.io/badge/dependabot-enabled-blue.svg?logo=dependabot&style=flat-square" alt="Dependabot"></a>
    <a href="https://github.com/sponsors/satnamgills"><img src="https://img.shields.io/badge/sponsor-GitHub%20Sponsors-pink.svg?logo=github&style=flat-square" alt="GitHub Sponsors"></a>
    <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square" alt="License"></a>
</p>

A modern, fluent, and strongly-typed **SBI ePay** (State Bank of India Payment Gateway) integration package for Laravel applications.

> [!NOTE]
> **Disclaimer**: This is an independent, community-developed package and is **not** an official package from State Bank of India (SBI) or SBI ePay. It is neither endorsed by, affiliated with, nor maintained by State Bank of India.

---

## Highlights

- ⚡ **Fluent Payment Initiation**: One-liner payment generation with auto-submitting redirect form or headless API responses for SPAs.
- 🔐 **AES-128-CBC Encryption**: Compliant with official SBI ePay specifications using PHP's native OpenSSL.
- 🛡️ **Double Verification Support**: Built-in server-to-server query verification to prevent client tampering and guarantee authentic bank status.
- 🏢 **Multi-Merchant / Multi-Tenant Support**: Seamlessly switch merchant credentials dynamically using `SbiEpay::merchant($id, $key)`.
- 🔀 **Environment Switching**: Out-of-the-box configuration for `test` (UAT) and `live` (Production) gateways.
- 🧪 **Production-Grade Quality**: Strict types declared, PHPStan Level 7 compliant, 100% type coverage, and tested with Pest.

---

## Requirements

- **PHP**: `^8.3` or `^8.4`
- **Laravel**: `^11.0` or `^12.0`
- **PHP OpenSSL Extension** enabled

---

## Installation

Install the package into your Laravel application via Composer:

```bash
composer require satnam-singh/sbi-epay
```

### Publish Configuration

Publish the `sbi-epay.php` configuration file:

```bash
php artisan vendor:publish --tag="sbi-epay-config"
```

---

## Configuration

Add your SBI ePay merchant credentials to your `.env` file:

```dotenv
# Environment: 'test' for UAT sandbox, 'live' for production
SBI_EPAY_MODE=test

# SBI ePay Merchant Credentials
SBI_EPAY_MERCHANT_ID=1000003
SBI_EPAY_MERCHANT_KEY=your_16_byte_merchant_key

# Optional settings (sensible defaults provided)
SBI_EPAY_AGGREGATOR_ID=SBIEPAY
SBI_EPAY_OPERATING_MODE=2
SBI_EPAY_COUNTRY=IN
SBI_EPAY_CURRENCY=INR

# Optional URL overrides
# SBI_EPAY_PAYMENT_URL=https://test.sbiepay.sbi/secure/AggregatorHostedListener
# SBI_EPAY_VERIFICATION_URL=https://test.sbiepay.sbi/secure/DoubleVerificationGateway
```

---

## Important: Exclude Callback Route from CSRF Verification

SBI ePay returns customers back to your application by issuing a cross-site **HTTP POST** request containing encrypted payload data (`encData`). In Laravel 11 and 12, make sure to exclude your payment callback route from CSRF verification:

In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'payment/sbi/callback',
    ]);
})
```

---

## Quick Usage

### 1. Initiating a Payment

In your controller, use the `SbiEpay` facade to create a payment request:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SbiEpay\SbiEpay\Facades\SbiEpay;
use SbiEpay\SbiEpay\DTOs\PaymentRequest;

class CheckoutController extends Controller
{
    public function initiatePayment()
    {
        $initiation = SbiEpay::initiate(new PaymentRequest(
            orderId: 'ORDER_' . uniqid(),
            amount: 1500.00,
            successUrl: route('payment.sbi.callback'),
            failUrl: route('payment.sbi.callback'),
            otherDetails: 'Order Payment #1024',
            customerEmail: 'customer@example.com',
            customerMobile: '9876543210',
        ));

        // Returns an auto-submitting POST view that redirects the user to SBI ePay
        return $initiation->redirect();
    }
}
```

> **For Headless / Inertia / Vue / React SPAs:**
> Instead of `$initiation->redirect()`, retrieve the raw form parameters and submit them from your frontend:
> ```php
> return response()->json([
>     'action' => $initiation->paymentUrl,
>     'form_data' => $initiation->formData(),
> ]);
> ```

---

### 2. Handling the Payment Callback

When the transaction finishes, SBI ePay posts the encrypted status payload to your `successUrl` or `failUrl`. Decrypt and parse it with `SbiEpay::handleCallback()`:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SbiEpay\SbiEpay\Facades\SbiEpay;

class CheckoutController extends Controller
{
    public function handleCallback(Request $request)
    {
        try {
            // Decrypt and parse the response
            $response = SbiEpay::handleCallback($request->input('encData'));

            if ($response->isSuccess()) {
                // Highly recommended: Perform server-to-server Double Verification
                $verification = SbiEpay::verify($response->orderId, $response->amount);

                if ($verification->isSuccess()) {
                    // Mark order as paid in your database
                    // $order = Order::where('order_id', $response->orderId)->first();
                    // $order->markAsPaid($response->sbiTransactionId);

                    return redirect()->route('order.success', [
                        'order_id' => $response->orderId,
                    ])->with('success', 'Payment completed successfully!');
                }
            }

            return redirect()->route('order.failed')->with('error', 'Payment verification failed.');

        } catch (\Exception $e) {
            return redirect()->route('order.failed')->with('error', $e->getMessage());
        }
    }
}
```

#### Available Methods on `PaymentResponse`:

```php
$response->isSuccess();           // bool: true if status is SUCCESS
$response->isFailed();            // bool: true if status is FAIL/FAILED
$response->isPending();           // bool: true if status is PENDING/AWAITED
$response->orderId;               // string: your original order ID
$response->status;                // string: 'SUCCESS', 'FAIL', etc.
$response->amount;                // float: paid amount
$response->sbiTransactionId;      // ?string: SBI reference ID
$response->bankReferenceNo;       // ?string: Bank reference number
$response->bankName;              // ?string: Paying bank name (e.g. State Bank of India)
$response->transactionDate;       // ?string: Transaction timestamp
$response->merchantId;            // ?string: SBI merchant ID
$response->rawResponse;           // string: raw pipe-delimited decrypted string
$response->toArray();             // array<string, mixed>
```

---

### 3. Server-to-Server Double Verification

To eliminate any chance of client-side callback tampering, query SBI's Double Verification API directly from your server:

```php
use SbiEpay\SbiEpay\Facades\SbiEpay;

$verification = SbiEpay::verify(
    orderId: 'ORDER_12345',
    amount: 1500.00
);

if ($verification->isSuccess()) {
    // Verified by SBI server
    $sbiRef = $verification->sbiTransactionId;
}
```

---

### 4. Dynamic Multi-Merchant Support

If your application processes payments for multiple branches, municipalities, or tenants with different merchant accounts:

```php
$initiation = SbiEpay::merchant('MERCHANT_BRANCH_02', 'KEY_BRANCH_02')
    ->initiate($paymentRequest);
```

---

### 5. Direct Encryption & Decryption Helpers

You can also use the encryption engine standalone:

```php
use SbiEpay\SbiEpay\Facades\SbiEpay;

// Encrypt string with configured merchant key
$cipherText = SbiEpay::encrypt('Hello World');

// Decrypt back to plaintext
$plainText = SbiEpay::decrypt($cipherText);
```

---

## Testing

Run the package test suite:

```bash
composer test
```

Individual checks:

```bash
# Pest test suite
composer test:unit

# Type coverage (100%)
composer test:types

# PHPStan static analysis
composer analyse

# Laravel Pint code styling
composer lint:check
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for details on recent changes.

---

## Contributing

Please see [CONTRIBUTING.md](.github/CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](.github/CODE_OF_CONDUCT.md) for details.

---

## Security

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

---

## Credits

- **Satnam Singh** ([@satnamgills](https://github.com/satnamgills))
- [All Contributors](../../contributors)

---

## Disclaimer

This package is an independent, custom-built open-source integration developed to assist Laravel developers integrating with the SBI ePay payment gateway. This project is **not** an official product of, nor is it endorsed, certified, or maintained by State Bank of India (SBI) or SBI ePay. All trademarks, service marks, and company names are the property of their respective owners.

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
