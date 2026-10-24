<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAC Registration Update - SimPhix</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; }
        .wrapper { width: 100%; background-color: #f8fafc; padding: 40px 20px; box-sizing: border-box; }
        .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1e293b 0%, #42517c 100%); padding: 32px; text-align: center; }
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
            <div class="header"><span style="color:#ffffff;font-size:22px;font-weight:800;letter-spacing:1px;">SimPhix</span></div>
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
            </div>
        </div>
    </div>
</body>
</html>
