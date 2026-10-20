<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; text-align: center; padding: 24px;">
    <h2>Hi {{ $registration->username }}, your registration is confirmed!</h2>
    <p>Please show this QR code at the door to check in.</p>

    <img src="{{ $message->embedData($qrPng, 'qr-code.png', 'image/png') }}"
         width="250" height="250" alt="QR code">

    <p style="color:#666;">Email: {{ $registration->email }}<br>Phone: {{ $registration->phone }}</p>
</body>
</html>
