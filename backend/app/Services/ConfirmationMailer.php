<?php

namespace App\Services;

use App\Mail\ConfirmationMail;
use App\Models\EmailLog;
use App\Models\Registration;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ConfirmationMailer
{
    // Send QR email via Gmail → save in email_logs
    // $type = 'qr' (after register) | 'resend' (admin button), $sentBy = admin id, null = system
    public function send(Registration $registration, string $type, ?int $sentBy = null): bool
    {
        $error = null;

        try {
            Mail::to($registration->email)->send(new ConfirmationMail($registration, $this->qrPng($registration)));
            $registration->update(['email_sent_at' => now()]);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        // INSERT email_logs → sent or failed
        EmailLog::create([
            'registration_id' => $registration->id,
            'sent_by'         => $sentBy,
            'type'            => $type,
            'status'          => $error === null ? 'sent' : 'failed',
            'error_message'   => $error,
            'sent_at'         => now(),
        ]);

        return $error === null;
    }

    // QR image from the token (same in the email and on the page)
    public function qrPng(Registration $registration): string
    {
        return (new PngWriter())
            ->write(new QrCode(data: $registration->token, size: 300, margin: 10))
            ->getString();
    }
}
