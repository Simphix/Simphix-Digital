<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAC Registration Update - SimPhix</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; }
        .wrapper { width: 100%; background-color: #f8fafc; padding: 40px 20px; box-sizing: border-box; }
        .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; border-top: 4px solid #0056D2; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background-color: #ffffff; padding: 28px 24px 22px; text-align: center; border-bottom: 1px solid #f1f5f9; }
        .header img { height: 40px; max-height: 40px; width: auto; }
        .content { padding: 40px 32px; }
        .title { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0 0 16px 0; text-align: center; }
        .text { font-size: 15px; line-height: 24px; color: #475569; margin: 0 0 20px 0; }
        .box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 24px 0; font-size: 14px; }
        .row { margin-bottom: 8px; } .label { color: #64748b; font-weight: 600; } .val { color: #1e293b; font-weight: 700; }
        .btn { display: inline-block; background: linear-gradient(135deg, #42517c 0%, #55699e 100%); color: #ffffff !important; text-decoration: none; padding: 14px 30px; font-weight: 600; font-size: 15px; border-radius: 12px; }
        .footer { background-color: #f8fafc; padding: 24px 32px; text-align: center; border-top: 1px solid #f1f5f9; }
        .footer-text { font-size: 12px; color: #94a3b8; margin: 0 0 8px 0; }
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
                <h1 class="title">CAC Registration Update</h1>
                <p class="text">Hello {{ $registration->user->first_name }},</p>
                <p class="text">{{ $updateMessage }}</p>

                <div class="box">
                    <div class="row"><span class="label">Reference:</span> <span class="val">{{ $registration->reference }}</span></div>
                    <div class="row"><span class="label">Type:</span> <span class="val">{{ $registration->category_label }}</span></div>
                    <div class="row"><span class="label">Status:</span> <span class="val">{{ $registration->status_label }}</span></div>
                </div>

                <p style="text-align:center;margin:32px 0;"><a href="{{ route('cac.show', $registration) }}" class="btn">View Registration</a></p>
            </div>
            <div class="footer">
                <p class="footer-text">© {{ date('Y') }} SimPhix. All rights reserved.</p>
                <p class="footer-text">Empowering Businesses Through Smart Connectivity.</p>
            </div>
        </div>
    </div>
</body>
</html>
