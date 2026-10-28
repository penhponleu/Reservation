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

                <p style="color:#666; font-size:14px; margin-bottom:0;">Email: {{ $registration->email }}<br>Phone: {{ $registration->phone }}</p>

                <!-- link to the scan page: opens the camera to scan QR codes -->
                @php($scanUrl = config('app.frontend_url').'/admin/scan')
                <p style="font-size:14px; margin:16px 0 0;">Scan QR: <a href="{{ $scanUrl }}" style="color:#5b4bd6;">{{ $scanUrl }}</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
