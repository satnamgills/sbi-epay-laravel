# SBI ePay Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/satnam-singh/sbi-epay.svg?style=flat-square)](https://packagist.org/packages/satnam-singh/sbi-epay)
[![Total Downloads](https://img.shields.io/packagist/dt/satnam-singh/sbi-epay.svg?style=flat-square)](https://packagist.org/packages/satnam-singh/sbi-epay)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)

A modern, fluent, and strongly-typed **SBI ePay** (State Bank of India Payment Gateway) integration package for Laravel applications.

---

## Features

- ⚡ **Fluent Payment Initiation**: One-liner payment generation with auto-submitting redirect form or headless API responses.
- 🔐 **Robust OpenSSL Encryption**: Built-in AES-128-CBC encryption and decryption complying with SBI ePay specifications.
- 🛡️ **Double Verification**: Built-in server-to-server query verification to prevent spoofing and guarantee authentic bank status.
- 🏢 **Multi-Merchant / Tenant Support**: Set dynamic credentials on the fly using `SbiEpay::merchant($id, $key)`.
- 🔀 **Environment Switching**: Seamless toggle between `test` and `live` endpoints.
- 🧪 **100% Type Coverage & Tested**: Built with PHP 8.2+, Pest 4, PHPStan Level 7.

---

## Installation

Install the package via Composer:

```bash
composer require satnam-singh/sbi-epay
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="sbi-epay-config"
```

Optionally publish the redirect view:

```bash
php artisan vendor:publish --tag="sbi-epay-views"
```

---

## Configuration

Add your SBI ePay merchant credentials in your `.env` file:

```env
SBI_EPAY_MODE=test # or live
SBI_EPAY_MERCHANT_ID=1000003
SBI_EPAY_MERCHANT_KEY=your_16_or_32_byte_merchant_key

# Optional endpoint overrides
# SBI_EPAY_PAYMENT_URL=https://test.sbiepay.sbi/secure/AggregatorHostedListener
# SBI_EPAY_VERIFICATION_URL=https://test.sbiepay.sbi/secure/DoubleVerificationGateway
```

---

## Usage

### 1. Initiating a Payment

In your controller, use the `SbiEpay` facade to initiate a transaction:

```php
use SbiEpay\SbiEpay\Facades\SbiEpay;
use SbiEpay\SbiEpay\DTOs\PaymentRequest;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function checkout()
    {
        $initiation = SbiEpay::initiate(new PaymentRequest(
            orderId: 'BILL_2026_001',
            amount: 1500.00,
            successUrl: route('sbi.callback'),
            failUrl: route('sbi.callback'),
            otherDetails: 'Property Tax Payment'
        ));

        // Returns an auto-submitting POST view that redirects to SBI ePay
        return $initiation->redirect();
    }
}
```

> **For Headless / SPA Applications (Inertia / Vue / React):**
> You can retrieve the raw encrypted parameters using:
> ```php
> return response()->json($initiation->formData());
> ```

---

### 2. Handling the Payment Callback

SBI ePay posts the encrypted transaction result to your `successUrl` or `failUrl`. Decrypt and parse it with `handleCallback()`:

```php
public function callback(Request $request)
{
    try {
        $response = SbiEpay::handleCallback($request->input('encData'));

        if ($response->isSuccess()) {
            $orderId = $response->orderId;
            $sbiRef = $response->sbiTransactionId;
            $amount = $response->amount;

            // Optional recommended step: Double verification with SBI server
            $verification = SbiEpay::verify($orderId, $amount);

            if ($verification->isSuccess()) {
                // Mark your order/invoice as paid
                return redirect()->route('payment.receipt', $orderId)
                    ->with('success', 'Payment processed successfully!');
            }
        }

        return redirect()->route('payment.failed')
            ->with('error', 'Payment failed or cancelled.');

    } catch (\Exception $e) {
        return redirect()->route('payment.failed')
            ->with('error', $e->getMessage());
    }
}
```

---

### 3. Server-to-Server Double Verification

Perform an on-demand status check directly with SBI ePay:

```php
use SbiEpay\SbiEpay\Facades\SbiEpay;

$verification = SbiEpay::verify(
    orderId: 'BILL_2026_001',
    amount: 1500.00
);

if ($verification->isSuccess()) {
    // Payment verified directly from bank server
}
```

---

### 4. Dynamic Multi-Merchant Credentials

If your application manages multiple municipalities, branches, or merchants:

```php
$initiation = SbiEpay::merchant('MERCHANT_02', 'KEY_FOR_MERCHANT_02')
    ->initiate($paymentRequest);
```

---

## Testing

Run the package test suite:

```bash
composer test
```

---

## Security

If you discover any security vulnerabilities, please email `satnamgills@gmail.com`.

---

## Credits

- **Satnam Singh** ([@satnamgills](https://github.com/satnamgills))
- All Contributors

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
