<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to SimPhix</title>
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
        .features-container {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin: 28px 0;
        }
        .feature-item {
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
        }
        .feature-item:last-child {
            margin-bottom: 0;
        }
        .feature-icon {
            color: #2ebe59;
            font-weight: bold;
            font-size: 18px;
            margin-right: 12px;
            line-height: 1;
        }
        .feature-text-group {
            flex: 1;
        }
        .feature-title {
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
            margin: 0 0 4px 0;
        }
        .feature-desc {
            color: #64748b;
            font-size: 13px;
            margin: 0;
            line-height: 18px;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #42517c 0%, #55699e 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 30px;
            font-weight: 600;
            font-size: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(66, 81, 124, 0.2);
            transition: all 0.2s ease;
        }
        .btn:hover {
            box-shadow: 0 6px 14px rgba(66, 81, 124, 0.3);
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
                <span style="color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: 1px;">SimPhix</span>
            </div>
            <div class="content">
                <h1 class="title">Welcome Aboard, {{ $user->first_name }}!</h1>
                <p class="text">Congratulations! Your KYC profile has been completed and verified successfully. Your account is now fully active at <strong>Tier 1 (Basic Verified)</strong> status.</p>
                
                <p class="text">At SimPhix, we pride ourselves on providing the fastest, most reliable, and most affordable data subscription services in Nigeria. Whether you are funding your wallet, purchasing data, or paying utility bills, we are built to give you the absolute best experience.</p>
                
                <div class="features-container">
                    <div class="feature-item">
                        <span class="feature-icon">✓</span>
                        <div class="feature-text-group">
                            <h4 class="feature-title">Lightning-Fast Delivery</h4>
                            <p class="feature-desc">Enjoy instant, automated delivery on all data bundles, airtime, and utility purchases.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">✓</span>
                        <div class="feature-text-group">
                            <h4 class="feature-title">Unbeatable Affordability</h4>
                            <p class="feature-desc">Access the cheapest data plans and top-tier margins designed to save you money.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">✓</span>
                        <div class="feature-text-group">
                            <h4 class="feature-title">Bank-Grade Security</h4>
                            <p class="feature-desc">Your wallet funds and transaction history are protected by top-tier encryption standards.</p>
                        </div>
                    </div>
                </div>

                <div class="btn-container">
                    <a href="{{ route('dashboard') }}" class="btn">Explore Your Dashboard</a>
                </div>
                
                <p class="text" style="font-size: 14px; text-align: center; margin-bottom: 0; color: #64748b;">
                    Thank you for choosing SimPhix. Let's build smart connections together!
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
