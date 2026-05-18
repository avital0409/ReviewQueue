<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Submission Rejection Notice</title>
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
            background-color: #f1f5f9;
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
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
        .reason-box {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .reason-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ef4444;
            margin-bottom: 8px;
        }
        .reason-text {
            font-size: 14px;
            color: #991b1b;
            margin: 0;
            font-style: italic;
        }
        .submission-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .submission-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 8px;
        }
        .submission-text {
            font-size: 13.5px;
            color: #334155;
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
            <h1>ReviewQueue Content Notice</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Thank you for submitting your content to our platform. After careful evaluation by our moderation team, we regret to inform you that your submission has been <strong>rejected</strong> as it does not meet our community standards or heuristic policies.</p>
            
            <div class="reason-box">
                <div class="reason-title">Reason for Rejection</div>
                <p class="reason-text">{{ $rejectionReason }}</p>
            </div>

            <div class="submission-box">
                <div class="submission-title">Your Submitted Content</div>
                <p class="submission-text">{{ $submissionContent }}</p>
            </div>

            <p>If you have any questions or would like to submit an appeal, please contact support.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} ReviewQueue Moderation Hub. All rights reserved.
        </div>
    </div>
</body>
</html>
