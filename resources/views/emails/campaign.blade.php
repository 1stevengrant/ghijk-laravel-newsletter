<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $campaign->subject ?? $campaign->name }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        .unsubscribe-link {
            color: #666;
            text-decoration: none;
        }
        .unsubscribe-link:hover {
            text-decoration: underline;
        }
        .tracking-pixel {
            width: 1px;
            height: 1px;
            display: block;
        }
        h1, h2, h3 {
            color: #2563eb;
        }
        a {
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="content">
            <h2>{{ $campaign->subject ?? $campaign->name }}</h2>

            @if(isset($content) && $content)
                {!! $content !!}
            @elseif($campaign->content)
                {!! nl2br(e($campaign->content)) !!}
            @else
                <p>Hello {{ $subscriber->name ?? 'there' }},</p>
                <p>Thank you for subscribing to our newsletter!</p>
                <p>This is a sample campaign email. You can customize this content in your campaign settings.</p>
            @endif
        </div>

        <div class="footer">
            <p>
                You received this email because you subscribed to {{ $campaign->newsletterList->name }}.
            </p>
            <p>
                <a href="{{ $unsubscribeUrl }}" class="unsubscribe-link">
                    Unsubscribe from this list
                </a>
            </p>
            <p>
                Sent by {{ $campaign->newsletterList->from_name }}
                &lt;{{ $campaign->newsletterList->from_email }}&gt;
            </p>
        </div>
    </div>

    <!-- Tracking pixel for open tracking -->
    <img src="{{ $trackingPixelUrl }}" alt="" class="tracking-pixel">
</body>
</html>
