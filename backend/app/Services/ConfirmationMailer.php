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
    /**
     * Make QR code → send email via Gmail → Sent?
     * $type   = 'qr' (after register) | 'resend' (admin button)  ← enum values from the ERD
     * $sentBy = admin id, null = system
     */
    public function send(Registration $registration, string $type, ?int $sentBy = null): bool
    {
        try {
            $qrPng = $this->qrPng($registration);

            // Send email via Gmail
            Mail::to($registration->email)->send(new ConfirmationMail($registration, $qrPng));

            // Sent? YES → INSERT email_logs status = sent
            EmailLog::create([
                'registration_id' => $registration->id,
                'sent_by'         => $sentBy,
                'type'            => $type,
                'status'          => 'sent',
                'sent_at'         => now(),
            ]);

            // → UPDATE registrations email_sent_at = now
            $registration->update(['email_sent_at' => now()]);

            return true;
        } catch (Throwable $e) {
            // Sent? NO → INSERT email_logs status = failed, error_message
            EmailLog::create([
                'registration_id' => $registration->id,
                'sent_by'         => $sentBy,
                'type'            => $type,
                'status'          => 'failed',
                'error_message'   => $e->getMessage(),
                'sent_at'         => now(),
            ]);

            return false;
        }
    }

    // Make QR code from the registration token (same image in the email and on the page)
    public function qrPng(Registration $registration): string
    {
        return (new PngWriter())
            ->write(new QrCode(data: $registration->token, size: 300, margin: 10))
            ->getString();
    }
}
