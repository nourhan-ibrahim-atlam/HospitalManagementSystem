<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="x-apple-disable-message-reformatting">
<title>{{ config('app.name') }} - Email Verification</title>
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<![endif]-->
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; background: #EBF4F8; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
  table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
  img { border: 0; outline: none; text-decoration: none; }
  a { text-decoration: none; }
  .wrapper { width: 100%; background: #EBF4F8; padding: 40px 16px; }
  .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; border: 1px solid rgba(10,77,104,.08); box-shadow: 0 2px 8px rgba(10,77,104,.06), 0 12px 40px rgba(10,77,104,.10); }
  .header { background: linear-gradient(150deg, #083B55 0%, #0A5470 45%, #0C6B8A 100%); padding: 40px 44px 0; text-align: center; position: relative; overflow: hidden; }
  .header::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse 340px 260px at 50% -40px, rgba(0,180,216,.22) 0%, transparent 70%), radial-gradient(ellipse 200px 200px at 100% 110%, rgba(0,180,216,.14) 0%, transparent 70%), radial-gradient(ellipse 160px 160px at -10% 90%, rgba(255,255,255,.05) 0%, transparent 70%); pointer-events: none; }
  .brand { display: flex; align-items: center; justify-content: center; gap: 12px; position: relative; z-index: 2; margin-bottom: 36px; }
  .brand-logo { width: 42px; height: 42px; border-radius: 11px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .brand-name { font-size: 20px; font-weight: 800; color: #fff; letter-spacing: -.4px; line-height: 1.1; }
  .brand-sub  { font-size: 9.5px; color: rgba(255,255,255,.50); letter-spacing: 2px; text-transform: uppercase; margin-top: 2px; }
  .hero-wrap  { position: relative; z-index: 2; display: flex; align-items: center; justify-content: center; margin-bottom: 28px; }
  .hero-rings { position: relative; width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; }
  .ring       { position: absolute; border-radius: 50%; border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.04); }
  .ring-1     { inset: 0; }
  .ring-2     { inset: 14px; border-color: rgba(0,180,216,.25); background: rgba(0,180,216,.06); }
  .ring-core  { position: relative; z-index: 2; width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(145deg, rgba(0,180,216,.30) 0%, rgba(14,107,143,.50) 100%); border: 1px solid rgba(0,180,216,.55); box-shadow: 0 0 0 6px rgba(0,180,216,.08), 0 8px 28px rgba(0,0,0,.30); display: flex; align-items: center; justify-content: center; }
  .badge      { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.18); border-radius: 100px; padding: 5px 14px; margin-bottom: 14px; position: relative; z-index: 2; }
  .badge-dot  { width: 6px; height: 6px; border-radius: 50%; background: #90E0EF; box-shadow: 0 0 6px #90E0EF; flex-shrink: 0; }
  .badge span { font-size: 10px; color: rgba(255,255,255,.80); letter-spacing: 1.6px; text-transform: uppercase; font-weight: 600; }
  .header-title { font-size: 28px; font-weight: 800; color: #fff; letter-spacing: -.6px; line-height: 1.2; position: relative; z-index: 2; margin-bottom: 8px; }
  .header-title em { font-style: normal; color: #90E0EF; }
  .header-sub { font-size: 13.5px; color: rgba(255,255,255,.50); line-height: 1.6; position: relative; z-index: 2; font-weight: 400; margin-bottom: 32px; }
  .wave { display: block; width: 100%; line-height: 0; position: relative; z-index: 2; }
  .wave svg { display: block; width: 100%; }
  .body    { padding: 36px 44px 32px; background: #fff; }
  .greeting{ font-size: 19px; font-weight: 700; color: #0A3D52; margin-bottom: 14px; letter-spacing: -.3px; }
  .para    { font-size: 14.5px; color: #4A6878; line-height: 1.75; margin-bottom: 10px; }
  .step-card { display: flex; align-items: flex-start; gap: 14px; background: linear-gradient(135deg, #F0F8FC 0%, #F7FBFE 100%); border: 1px solid #C6DDE8; border-left: 3px solid #00B4D8; border-radius: 12px; padding: 16px 18px; margin: 26px 0; }
  .step-num  { width: 30px; height: 30px; border-radius: 8px; background: #00B4D8; color: #fff; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 3px 10px rgba(0,180,216,.35); }
  .step-label{ font-size: 10.5px; font-weight: 700; color: #0A4D68; text-transform: uppercase; letter-spacing: 1.4px; margin-bottom: 4px; }
  .step-text { font-size: 13px; color: #4A6878; line-height: 1.6; }
  .cta-wrap  { text-align: center; margin: 30px 0; }
  .cta-btn   { display: inline-block; padding: 15px 48px; border-radius: 12px; font-size: 14.5px; font-weight: 700; letter-spacing: .2px; color: #fff !important; position: relative; overflow: hidden; }
  .cta-btn.primary { background: linear-gradient(135deg, #0A5470 0%, #0A85A8 60%, #00B4D8 100%); box-shadow: 0 6px 24px rgba(10,133,168,.35), 0 2px 6px rgba(0,0,0,.10); }
  .cta-btn.success { background: linear-gradient(135deg, #0D9E6E 0%, #10C988 100%); box-shadow: 0 6px 24px rgba(13,158,110,.35); }
  .cta-btn.error   { background: linear-gradient(135deg, #C0392B 0%, #E74C3C 100%); box-shadow: 0 6px 24px rgba(192,57,43,.35); }
  .cta-btn::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 44%; background: rgba(255,255,255,.10); border-radius: 12px 12px 50% 50%; }
  .cta-label { position: relative; z-index: 1; }
  .sec-box   { display: flex; gap: 11px; align-items: flex-start; background: #FFFBF2; border: 1px solid #F5E0B0; border-radius: 11px; padding: 13px 15px; margin-top: 20px; }
  .sec-icon  { flex-shrink: 0; margin-top: 1px; }
  .sec-text  { font-size: 12px; color: #7A5310; line-height: 1.6; }
  .sec-text strong { color: #5C3D09; }
  hr.divider { border: none; border-top: 1px solid #E4EEF3; margin: 28px 0; }
  .outro     { font-size: 14px; color: #5A7D8C; line-height: 1.7; margin-bottom: 8px; }
  .sign      { margin-top: 24px; padding-top: 22px; border-top: 1px solid #E4EEF3; }
  .sign-regards { font-size: 10px; color: #A0BCC8; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; }
  .sign-name    { font-size: 17px; font-weight: 700; color: #0A3D52; letter-spacing: -.2px; }
  .sign-title   { font-size: 12px; color: #8AAEBB; margin-top: 2px; }
  .footer    { background: #F2F8FB; border-top: 1px solid #DDE9EE; padding: 26px 44px; }
  .url-copy  { font-size: 12px; color: #7A9BAB; line-height: 1.65; margin-bottom: 14px; }
  .url-box   { display: block; margin-top: 7px; word-break: break-all; font-family: 'Courier New', Courier, monospace; font-size: 10.5px; color: #0A4D68; background: #DDE9EE; border-radius: 7px; padding: 9px 13px; border: 1px solid #C8DDE6; }
  .legal     { margin-top: 14px; padding-top: 14px; border-top: 1px solid #DDE9EE; font-size: 11px; color: #A0B8C2; text-align: center; line-height: 1.7; }
  .legal a   { color: #5A7D8C; }
  @media only screen and (max-width: 480px) {
    .header { padding: 32px 24px 0; }
    .body   { padding: 28px 24px; }
    .footer { padding: 22px 24px; }
    .header-title { font-size: 22px; }
    .cta-btn { padding: 13px 28px; font-size: 13px; }
    .brand-name { font-size: 17px; }
  }
</style>
</head>
<body>
<div class="wrapper">
<div class="card">

  <div class="header">
    <div class="brand">
      <div>
        <div class="brand-name">{{ config('app.name') }}</div>
        <div class="brand-sub">Hospital Management System</div>
      </div>
    </div>

    <div class="hero-wrap">
      <div class="hero-rings">
        <div class="ring ring-1"></div>
        <div class="ring ring-2"></div>
        <div class="ring-core">
          @if (isset($level) && $level === 'error')
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
              <circle cx="16" cy="16" r="10" stroke="white" stroke-width="2" opacity="0.90"/>
              <path d="M16 11v6M16 20h.01" stroke="white" stroke-width="2.2" stroke-linecap="round" opacity="0.90"/>
            </svg>
          @elseif (isset($level) && $level === 'success')
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
              <circle cx="16" cy="16" r="10" stroke="white" stroke-width="2" opacity="0.90"/>
              <path d="M11 16.5l3.5 3.5 7-7" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" opacity="0.90"/>
            </svg>
          @else
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
              <rect x="13" y="3"  width="6"  height="26" rx="2.5" fill="white"   opacity="0.92"/>
              <rect x="3"  y="13" width="26" height="6"  rx="2.5" fill="white"   opacity="0.92"/>
              <rect x="13" y="3"  width="6"  height="26" rx="2.5" fill="#90E0EF" opacity="0.35"/>
            </svg>
          @endif
        </div>
      </div>
    </div>

    <div class="badge">
      <div class="badge-dot"></div>
      <span>Secure Notification</span>
    </div>

    @if (! empty($greeting))
      <div class="header-title">{{ $greeting }}</div>
    @elseif (isset($level) && $level === 'error')
      <div class="header-title">Action <em>Required</em></div>
      <div class="header-sub">Please review this important notice regarding your account.</div>
    @else
      <div class="header-title">Verify Your <em>Email</em></div>
      <div class="header-sub">Confirm your identity to access the {{ config('app.name') }} portal securely.</div>
    @endif

    <div class="wave">
      <svg viewBox="0 0 600 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0,20 C120,40 240,0 360,20 C480,40 540,10 600,20 L600,40 L0,40 Z" fill="#ffffff"/>
      </svg>
    </div>
  </div>

  <div class="body">
    @foreach ($introLines as $line)
      <p class="para">{{ $line }}</p>
    @endforeach

    @isset($actionText)
      <?php $color = match($level ?? 'primary') { 'success' => 'success', 'error' => 'error', default => 'primary' }; ?>
      <div class="step-card">
        <div class="step-num">01</div>
        <div>
          <div class="step-label">Next Step</div>
          <div class="step-text">Click the button below to {{ strtolower($actionText) }}. This link is encrypted and valid for a limited time only.</div>
        </div>
      </div>
      <div class="cta-wrap">
        <a href="{{ $actionUrl }}" class="cta-btn {{ $color }}" target="_blank" rel="noopener">
          <span class="cta-label">{{ $actionText }} &nbsp;→</span>
        </a>
      </div>
      <div class="sec-box">
        <div class="sec-icon">
          <img src="">
        </div>
        <p class="sec-text"><strong>Security tip:</strong> {{ config('app.name') }} will never ask for your password via email. If you did not request this action, please contact your system administrator immediately.</p>
      </div>
    @endisset

    @if (count($outroLines) > 0)
      <hr class="divider">
      @foreach ($outroLines as $line)
        <p class="outro">{{ $line }}</p>
      @endforeach
    @endif

    <div class="sign">
      @if (! empty($salutation))
        <p class="outro">{{ $salutation }}</p>
      @else
        <div class="sign-regards">Warm regards,</div>
        <div class="sign-name">{{ config('app.name') }}</div>
        <div class="sign-title">Patient &amp; Staff Services Team</div>
      @endif
    </div>
  </div>

  <div class="footer">
    <div class="legal">
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
