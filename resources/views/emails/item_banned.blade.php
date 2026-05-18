<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Suspension & Permanent Ban Notice</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #fef2f2;
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid #fee2e2;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #991b1b;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 35px 30px;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 14px;
            color: #475569;
        }
        .ban-badge {
            display: inline-block;
            background-color: #ef4444;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 6px 12px;
            border-radius: 9999px;
            margin-bottom: 20px;
        }
        .reason-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .reason-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 8px;
        }
        .reason-text {
            font-size: 14px;
            color: #1e293b;
            margin: 0;
            white-space: pre-wrap;
        }
        .submission-box {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .submission-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #b91c1c;
            margin-bottom: 8px;
        }
        .submission-text {
            font-size: 13.5px;
            color: #991b1b;
            margin: 0;
            white-space: pre-wrap;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Account Suspension Notice</h1>
        </div>
        <div class="content">
            <div class="ban-badge">Permanent Ban</div>
            
            <p>Hello,</p>
            <p>This is a formal notice that your email address (<strong>{{ $authorEmail }}</strong>) has been <strong>permanently suspended</strong> from submitting content to ReviewQueue.</p>
            
            <p>Our Trust & Safety system identified repeated policy violations associated with your submissions, exceeding our allowed community standards limit (Strike 3 policy exceeded).</p>
            
            <div class="reason-box">
                <div class="reason-title">Official Ban Reason / Notes</div>
                <p class="reason-text">{{ $banReason }}</p>
            </div>

            <div class="submission-box">
                <div class="submission-title">Violating Submission Content</div>
                <p class="submission-text">{{ $submissionContent }}</p>
            </div>

            <p>As a result of this permanent suspension, any future submissions sent from your email address will be automatically rejected and blocked by our gateway filter.</p>
            
            <p>If you believe this suspension was made in error, you may reply to this notice to file an appeal with our senior moderation administration.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} ReviewQueue Moderation & Safety Hub. All rights reserved.
        </div>
    </div>
</body>
</html>
