<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>Redirecting to SBI ePay...</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            max-width: 440px;
            width: 100%;
            padding: 32px 24px;
            text-align: center;
        }
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #005a9c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        p {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .meta-box {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .meta-label {
            color: #64748b;
        }
        .meta-value {
            font-weight: 600;
            color: #0f172a;
        }
        .btn-submit {
            display: inline-block;
            background: #005a9c;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-submit:hover {
            background: #004475;
        }
    </style>
</head>
<body>

    <div class="card">
        <div class="spinner"></div>
        <h2>Redirecting to SBI ePay...</h2>
        <p>Please wait while we securely transfer you to the State Bank of India payment gateway. Please do not refresh or press back.</p>

        @if(isset($orderId) && isset($amount))
        <div class="meta-box">
            <div>
                <span class="meta-label">Order ID:</span>
                <span class="meta-value font-mono">{{ $orderId }}</span>
            </div>
            <div>
                <span class="meta-label">Amount:</span>
                <span class="meta-value">₹ {{ number_format((float) $amount, 2) }}</span>
            </div>
        </div>
        @endif

        <form id="sbi_epay_form" name="ecom" method="post" action="{{ $url }}">
            <input type="hidden" name="EncryptTrans" value="{{ $encryptedTrans }}">
            <input type="hidden" name="MultiAccountInstructionDtls" value="{{ $multiAccount }}">
            <input type="hidden" name="merchIdVal" value="{{ $merchantId }}"/>

            <noscript>
                <p style="color: #dc2626; font-size: 12px; margin-top: 10px;">JavaScript is disabled. Please click below to continue:</p>
                <button type="submit" class="btn-submit">Proceed to Payment</button>
            </noscript>
        </form>

        <div style="margin-top: 16px;">
            <button type="button" onclick="document.getElementById('sbi_epay_form').submit();" class="btn-submit" style="font-size: 12px; padding: 6px 14px; background: #64748b;">
                Click here if not redirected automatically
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var form = document.getElementById('sbi_epay_form');
                if (form) {
                    form.submit();
                }
            }, 300);
        });
    </script>
</body>
</html>
