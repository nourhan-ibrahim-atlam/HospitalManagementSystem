<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ config('app.name') }} - Email Verification</title>

    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

    <style>
        /* ===== MEDIASYS BRAND SYSTEM ===== */
        :root {
            --ms-primary:       #0A4D68;
            --ms-primary-light: #0E6B8F;
            --ms-accent:        #00B4D8;
            --ms-accent-soft:   #90E0EF;
            --ms-success:       #0D9E6E;
            --ms-danger:        #D94040;
            --ms-bg:            #F0F7FA;
            --ms-surface:       #FFFFFF;
            --ms-border:        #C8DDE6;
            --ms-text:          #0D2B38;
            --ms-muted:         #5A7D8C;
            --ms-subtle:        #8AAEBB;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: #E8F4F8;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: inherit; }

        /* ===== OUTER WRAPPER ===== */
        .email-wrapper {
            width: 100%;
            background: linear-gradient(160deg, #D6EDF5 0%, #E8F4F8 60%, #CFE8F2 100%);
            padding: 40px 16px;
        }

        /* ===== CARD ===== */
        .email-card {
            max-width: 600px;
            margin: 0 auto;
            background: #FFFFFF;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 40px rgba(10, 77, 104, 0.10), 0 1px 4px rgba(10,77,104,0.06);
            border: 1px solid rgba(10,77,104,0.08);
        }

        /* ===== HEADER ===== */
        .email-header {
            background: linear-gradient(135deg, #0A4D68 0%, #0E6B8F 55%, #0899BE 100%);
            padding: 36px 40px 32px;
            position: relative;
            overflow: hidden;
        }

        .email-header::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(0, 180, 216, 0.12);
        }

        .email-header::after {
            content: '';
            position: absolute;
            bottom: -60px; left: 60px;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        .brand-icon {
            width: 48px; height: 48px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.25);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .brand-icon svg { width: 26px; height: 26px; }

        .brand-name {
            font-size: 22px;
            font-weight: 700;
            color: #FFFFFF;
            letter-spacing: -0.4px;
        }

        .brand-tagline {
            font-size: 11px;
            color: rgba(255,255,255,0.60);
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin-top: 1px;
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.20);
            border-radius: 100px;
            padding: 6px 14px;
            margin-bottom: 18px;
            position: relative; z-index: 1;
        }

        .header-badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #90E0EF;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .header-badge span {
            font-size: 11px;
            color: rgba(255,255,255,0.85);
            letter-spacing: 0.8px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            color: #FFFFFF;
            line-height: 1.25;
            letter-spacing: -0.5px;
            position: relative; z-index: 1;
        }

        .header-subtitle {
            font-size: 14px;
            color: rgba(255,255,255,0.65);
            margin-top: 6px;
            line-height: 1.5;
            position: relative; z-index: 1;
        }

        /* ===== BODY ===== */
        .email-body {
            padding: 20px 40px;
        }

        /* Level-specific accent strip */
        .level-strip {
            height: 3px;
            border-radius: 0 0 3px 3px;
            margin: 0 0 32px 0;
        }
        .level-strip.primary { background: linear-gradient(90deg, #0A4D68, #00B4D8); }
        .level-strip.success { background: linear-gradient(90deg, #0D9E6E, #34D399); }
        .level-strip.error   { background: linear-gradient(90deg, #D94040, #F87171); }

        /* Greeting */
        .greeting {
            font-size: 20px;
            font-weight: 700;
            color: #0D2B38;
            margin-bottom: 20px;
            letter-spacing: -0.3px;
        }

        /* Intro lines */
        .intro-line {
            font-size: 15px;
            color: #3D5D6B;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        /* Info box */
        .info-box {
            background: linear-gradient(135deg, #EBF6FB 0%, #F5FAFE 100%);
            border: 1px solid #C0DCE8;
            border-left: 4px solid #00B4D8;
            border-radius: 10px;
            padding: 16px 18px;
            margin: 24px 0;
        }

        .info-box-title {
            font-size: 11px;
            font-weight: 700;
            color: #0A4D68;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 4px;
        }

        .info-box-text {
            font-size: 13px;
            color: #3D5D6B;
            line-height: 1.55;
        }

        /* ===== ACTION BUTTON ===== */
        .action-wrapper {
            text-align: center;
            margin: 32px 0;
        }

        .action-btn {
            display: inline-block;
            padding: 16px 44px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0.2px;
            position: relative;
            overflow: hidden;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #0A4D68 0%, #0E6B8F 100%);
            color: #FFFFFF !important;
            box-shadow: 0 4px 20px rgba(10,77,104,0.35), 0 1px 4px rgba(10,77,104,0.2);
        }

        .action-btn.success {
            background: linear-gradient(135deg, #0D9E6E 0%, #10B37E 100%);
            color: #FFFFFF !important;
            box-shadow: 0 4px 20px rgba(13,158,110,0.35);
        }

        .action-btn.error {
            background: linear-gradient(135deg, #D94040 0%, #E85555 100%);
            color: #FFFFFF !important;
            box-shadow: 0 4px 20px rgba(217,64,64,0.35);
        }

        .action-btn::after {
            content: ' →';
            opacity: 0.75;
        }

        /* ===== DIVIDER ===== */
        .divider {
            border: none;
            border-top: 1px solid #DDE9EE;
            margin: 28px 0;
        }

        /* ===== OUTRO LINES ===== */
        .outro-line {
            font-size: 14px;
            color: #5A7D8C;
            line-height: 1.65;
            margin-bottom: 10px;
        }

        /* ===== SALUTATION ===== */
        .salutation {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid #DDE9EE;
        }

        .salutation-regards {
            font-size: 13px;
            color: #8AAEBB;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            margin-bottom: 8px;
        }

        .salutation-name {
            font-size: 17px;
            font-weight: 700;
            color: #0A4D68;
            letter-spacing: -0.2px;
        }

        .salutation-title {
            font-size: 12px;
            color: #8AAEBB;
            margin-top: 2px;
        }

        /* ===== FOOTER ===== */
        .email-footer {
            background: #F0F7FA;
            border-top: 1px solid #DDE9EE;
            padding: 24px 20px;
        }

        .footer-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .footer-meta-dot {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: #8AAEBB;
            flex-shrink: 0;
        }

        .footer-meta-text {
            font-size: 12px;
            color: #8AAEBB;
        }

        .subcopy {
            font-size: 12px;
            color: #8AAEBB;
            line-height: 1.65;
        }

        .subcopy-url {
            display: block;
            margin-top: 8px;
            word-break: break-all;
            color: #0A4D68;
            font-size: 11px;
            font-family: 'Courier New', Courier, monospace;
            background: #DDE9EE;
            border-radius: 6px;
            padding: 8px 12px;
        }

        .footer-legal {
            font-size: 11px;
            color: #AABFC9;
            text-align: center;
            line-height: 1.6;
        }

        .footer-legal a {
            color: #5A7D8C;
            text-decoration: none;
        }

        .security-notice {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #FFF8EC;
            border: 1px solid #F5CFA0;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 24px;
        }

        .security-icon {
            width: 18px; height: 18px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .security-text {
            font-size: 12px;
            color: #7A4E10;
            line-height: 1.55;
        }

        /* ===== RESPONSIVE ===== */
        @media only screen and (max-width: 480px) {
            .email-header { padding: 28px 24px 24px; }
            .email-body   { padding: 28px 24px; }
            .email-footer { padding: 20px 24px; }
            .header-title { font-size: 22px; }
            .action-btn   { padding: 14px 28px; font-size: 14px; }
            .brand-name   { font-size: 18px; }
        }
    </style>
</head>

<body>
<div class="email-wrapper">
<div class="email-card">

    {{-- ===== HEADER ===== --}}
    <div class="email-header">
        {{-- Brand Row --}}
        <div class="brand-row">
            <div>
                <div class="brand-name">MediaSYS</div>
                <div class="brand-tagline">Hospital Management System</div>
            </div>
        </div>

        {{-- Badge --}}
        <div class="header-badge">
            <span>Secure Notification</span>
        </div>

        {{-- Dynamic Greeting / Title --}}
        @if (! empty($greeting))
            <div class="header-title">{{ $greeting }}</div>
        @else
            @if ($level === 'error')
                <div class="header-title">Action Required</div>
                <div class="header-subtitle">Please review the following important notice regarding your account.</div>
            @else
                <div class="header-title">Email Verification</div>
                <div class="header-subtitle">Confirm your identity to access the MediaSYS portal securely.</div>
            @endif
        @endif
    </div>

    {{-- Level strip --}}
    <div class="level-strip {{ $level === 'success' ? 'success' : ($level === 'error' ? 'error' : 'primary') }}"></div>

    {{-- ===== BODY ===== --}}
    <div class="email-body">

        {{-- Intro Lines --}}
        @foreach ($introLines as $line)
            <p class="intro-line">{{ $line }}</p>
        @endforeach

        {{-- Action Button --}}
        @isset($actionText)
            <?php
                $color = match ($level) {
                    'success' => 'success',
                    'error'   => 'error',
                    default   => 'primary',
                };
            ?>

            <div class="info-box">
                <div class="info-box-title">Next Step</div>
                <div class="info-box-text">Click the button below to {{ strtolower($actionText) }}. This link is encrypted and valid for a limited time.</div>
            </div>

            <div class="action-wrapper">
                <a href="{{ $actionUrl }}" class="action-btn {{ $color }}" target="_blank" rel="noopener">
                    {{ $actionText }}
                </a>
            </div>

            {{-- Security notice --}}
            <div class="security-notice">
                <svg class="security-icon" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 1L2 4V9C2 12.87 5.08 16.47 9 17C12.92 16.47 16 12.87 16 9V4L9 1Z" stroke="#C07A1A" stroke-width="1.5" stroke-linejoin="round"/>
                    <path d="M9 8V12M9 6H9.01" stroke="#C07A1A" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span class="security-text">
                    <strong>Security tip:</strong> MediaSYS will never ask for your password via email. If you did not request this action, please contact your system administrator immediately and disregard this message.
                </span>
            </div>
        @endisset


        {{-- Salutation --}}
        <div class="salutation">
            @if (! empty($salutation))
                <p class="outro-line">{{ $salutation }}</p>
            @else
                <div class="salutation-regards">@lang('Warm regards,')</div>
                <div class="salutation-name">{{ config('app.name') }}</div>
                <div class="salutation-title">Patient & Staff Services Team</div>
            @endif
        </div>

    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="email-footer">


        {{-- Legal & meta --}}
        <div class="footer-legal">
            This is an automated message from <strong>{{ config('app.name') }}</strong>.<br>
            Please do not reply directly to this email.<br><br>
            &copy; {{ date('Y') }} {{ config('app.name') }} &mdash; All rights reserved.<br>
            <a href="#">Privacy Policy</a> &nbsp;&middot;&nbsp; <a href="#">Terms of Service</a> &nbsp;&middot;&nbsp; <a href="#">Contact Support</a>
        </div>
    </div>

</div>
</div>
</body>
</html>
