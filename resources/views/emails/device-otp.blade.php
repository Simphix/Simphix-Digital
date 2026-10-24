<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Device Sign-In - SimPhix</title>
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #42517c 100%);
            padding: 32px;
            text-align: center;
            position: relative;
        }
        .content {
            padding: 40px 32px;
            text-align: center;
        }
        .title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 12px 0;
        }
        .text {
            font-size: 15px;
            line-height: 24px;
            color: #64748b;
            margin: 0 0 20px 0;
        }
        .device-info {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 18px;
            margin: 0 0 24px 0;
            text-align: left;
            font-size: 13px;
            color: #475569;
        }
        .device-info strong {
            color: #1e293b;
        }
        .otp-container {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            display: inline-block;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #42517c;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
        }
        .expiry-text {
            font-size: 13px;
            color: #ef4444;
            font-weight: 500;
            margin: 12px 0 0 0;
        }
        .warning-text {
            font-size: 13px;
            color: #b45309;
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 12px 16px;
            margin: 0;
            text-align: left;
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
                <span style="color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: 1px;">SimPhix</span>
            </div>
            <div class="content">
                <h1 class="title">New Sign-In Verification</h1>
                <p class="text">We noticed a sign-in attempt on an unrecognized device. Enter the code below to finish signing in.</p>

                <div class="device-info">
                    <div><strong>Device:</strong> {{ $deviceLabel }}</div>
                    @if ($ip)
                        <div><strong>IP address:</strong> {{ $ip }}</div>
                    @endif
                    <div><strong>Time:</strong> {{ $requestedAt }}</div>
                </div>

                <div class="otp-container">
                    <div class="otp-code">{{ $otp }}</div>
                    <div class="expiry-text">Expires in 10 minutes</div>
                </div>

                <p class="warning-text">If this wasn't you, do not share this code with anyone and change your password immediately.</p>
            </div>
            <div class="footer">
                <p class="footer-text">© {{ date('Y') }} SimPhix. All rights reserved.</p>
                <p class="footer-text">Empowering Businesses Through Smart Connectivity.</p>
            </div>
        </div>
    </div>
</body>
</html>
