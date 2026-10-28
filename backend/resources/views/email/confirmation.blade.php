<!DOCTYPE html>
<html>
<body style="margin:0; padding:24px; background:#f4f5fb; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; border:1px solid #e6e3fb;">
        <tr>
            <td style="background:#5b4bd6; color:#fff; padding:24px; text-align:center;">
                <h2 style="margin:0;">Registration confirmed</h2>
            </td>
        </tr>
        <tr>
            <td style="padding:24px; text-align:center; color:#222;">
                <p style="font-size:16px; margin-top:0;">Hi <b>{{ $registration->fullname }}</b>, you're registered!</p>
                <p>Please show this QR code at the door to check in.</p>

                <img src="{{ $message->embedData($qrPng, 'qr-code.png', 'image/png') }}"
                     width="250" height="250" alt="QR code"
                     style="border:1px solid #eee; border-radius:8px;">

                <!-- link opens the QR code full-size in the browser (works even if images are hidden) -->
                <p style="margin:16px 0;">
                    <a href="{{ config('app.frontend_url') }}/qr/{{ $registration->token }}"
                       style="display:inline-block; background:#5b4bd6; color:#fff; text-decoration:none; font-weight:bold; padding:12px 24px; border-radius:8px;">
                        Open my QR code
                    </a>
                </p>

                <p style="color:#666; font-size:14px; margin-bottom:0;">Email: {{ $registration->email }}<br>Phone: {{ $registration->phone }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
