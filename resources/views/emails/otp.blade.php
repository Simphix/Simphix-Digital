<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - SimPhix</title>
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
            position: relative;
        }
        .header img {
            height: 40px;
            max-height: 40px;
            width: auto;
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
            margin: 0 0 28px 0;
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
        .footer-link {
            color: #42517c;
            text-decoration: none;
            font-weight: 500;
        }
        .footer-link:hover {
            text-decoration: underline;
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
                <h1 class="title">Verify Your Email Address</h1>
                <p class="text">Thank you for registering with SimPhix. Please use the following One-Time Password (OTP) to verify your email address and complete your registration.</p>
                
                <div class="otp-container">
                    <div class="otp-code">{{ $otp }}</div>
                    <div class="expiry-text">Expires in 15 minutes</div>
                </div>
                
                <p class="text" style="font-size: 14px; margin-bottom: 0;">If you didn't request this verification, you can safely ignore this email.</p>
            </div>
            <div class="footer">
                <p class="footer-text">© {{ date('Y') }} SimPhix. All rights reserved.</p>
                <p class="footer-text">Empowering Businesses Through Smart Connectivity.</p>
            </div>
        </div>
    </div>
</body>
</html>
