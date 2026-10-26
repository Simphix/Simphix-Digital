<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Notification - SimPhix</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding: 40px 20px;
            box-sizing: border-box;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            border-top: 4px solid #0056D2;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background-color: #ffffff;
            padding: 28px 24px 22px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .header img {
            height: 40px;
            max-height: 40px;
            width: auto;
        }
        .content {
            padding: 40px 32px;
        }
        .title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 16px 0;
            text-align: center;
        }
        .text {
            font-size: 15px;
            line-height: 24px;
            color: #475569;
            margin: 0 0 20px 0;
        }
        .amount-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            padding: 24px 20px;
            text-align: center;
            margin: 24px 0;
        }
        .amount-label {
            font-size: 12px;
            font-weight: 700;
            color: #166534;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 6px 0;
        }
        .amount-value {
            font-size: 34px;
            font-weight: 800;
            color: #15803d;
            margin: 0;
        }
        .details-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .details-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .details-row:first-child {
            padding-top: 0;
        }
        .details-label {
            color: #64748b;
            font-weight: 500;
        }
        .details-val {
            color: #1e293b;
            font-weight: 700;
            text-align: right;
            word-break: break-all;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0 16px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #0056D2 0%, #1e3a8a 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            font-weight: 600;
            font-size: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 86, 210, 0.25);
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
        }
        .footer-text {
            font-size: 12px;
            color: #94a3b8;
            margin: 0 0 8px 0;
            line-height: 18px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <a href="{{ config('app.url') }}" target="_blank" style="text-decoration: none; display: inline-block;">
                    <img src="{{ rtrim(config('app.url'), '/') . '/assets/images/logo/logo1.png' }}" alt="SimPhix" height="40" style="display: block; margin: 0 auto; height: 40px; max-height: 40px; width: auto; border: 0; outline: none; font-family: 'Outfit', -apple-system, sans-serif; font-size: 22px; font-weight: 800; color: #0056D2;" />
                </a>
            </div>
            <div class="content">
                <h1 class="title">Wallet Credited Successfully</h1>
                <p class="text">Hello,</p>
                <p class="text">Your SimPhix wallet has been successfully credited with new funds. The transaction details are outlined below:</p>

                <div class="amount-card">
                    <p class="amount-label">Credited Amount</p>
                    <p class="amount-value">₦{{ number_format((float) ($mail_data['amount'] ?? 0), 2) }}</p>
                </div>

                <div class="details-box">
                    <table style="width: 100%; border-collapse: collapse;">
                        @if (!empty($mail_data['ref']))
                        <tr>
                            <td style="padding: 8px 0; color: #64748b; font-size: 14px; font-weight: 500; border-bottom: 1px solid #f1f5f9;">Transaction Reference:</td>
                            <td style="padding: 8px 0; color: #1e293b; font-size: 14px; font-weight: 700; text-align: right; border-bottom: 1px solid #f1f5f9;">{{ $mail_data['ref'] }}</td>
                        </tr>
                        @endif
                        @if (!empty($mail_data['bankName']))
                        <tr>
                            <td style="padding: 8px 0; color: #64748b; font-size: 14px; font-weight: 500; border-bottom: 1px solid #f1f5f9;">Funding Channel:</td>
                            <td style="padding: 8px 0; color: #1e293b; font-size: 14px; font-weight: 700; text-align: right; border-bottom: 1px solid #f1f5f9;">{{ $mail_data['bankName'] }}</td>
                        </tr>
                        @endif
                        @if (!empty($mail_data['type']))
                        <tr>
                            <td style="padding: 8px 0; color: #64748b; font-size: 14px; font-weight: 500;">Payment Type:</td>
                            <td style="padding: 8px 0; color: #1e293b; font-size: 14px; font-weight: 700; text-align: right; text-transform: capitalize;">{{ $mail_data['type'] }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <div class="btn-container">
                    <a href="{{ route('wallet') }}" class="btn">View Wallet Balance</a>
                </div>

                <p class="text" style="font-size: 13px; color: #64748b; text-align: center; margin-top: 24px; margin-bottom: 0;">
                    Thank you for choosing SimPhix. If you have any questions, our support team is always ready to help.
                </p>
            </div>
            <div class="footer">
                <p class="footer-text">© {{ date('Y') }} SimPhix. All rights reserved.</p>
                <p class="footer-text">Empowering Businesses Through Smart Connectivity.</p>
            </div>
        </div>
    </div>
</body>
</html>
