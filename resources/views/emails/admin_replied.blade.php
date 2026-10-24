<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Ticket Reply - SimPhix</title>
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
        .reply-bubble {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin: 28px 0;
            border-left: 4px solid #42517c;
        }
        .reply-meta {
            font-weight: 700;
            color: #1e293b;
            font-size: 13px;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .reply-content {
            color: #334155;
            font-size: 14px;
            line-height: 22px;
            white-space: pre-wrap;
            margin: 0;
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
                <h1 class="title">New Reply from Support</h1>
                <p class="text">Hello {{ $ticket->user->first_name }},</p>
                <p class="text">An official support agent has replied to your ticket: <strong>"{{ $ticket->subject }}"</strong>.</p>
                
                <div class="reply-bubble">
                    <div class="reply-meta">
                        <span style="color: #42517c;">■</span> Dedicated Support Representative:
                    </div>
                    <p class="reply-content">{{ $reply->message }}</p>
                </div>

                <p class="text">To reply to this message or read the rest of the thread, please click the button below to open the support center on your dashboard.</p>

                <div class="btn-container">
                    <a href="{{ route('support.show', $ticket) }}" class="btn">View & Reply Thread</a>
                </div>
                
                <p class="text" style="font-size: 14px; text-align: center; margin-bottom: 0; color: #64748b;">
                    Thank you for choosing SimPhix. We are here to help!
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
