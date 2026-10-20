<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\ConfirmationMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function __construct(private ConfirmationMailer $mailer) {}

    // Guest clicks Register → Form valid?
    public function store(Request $request)
    {
        // No → Show errors (Laravel returns 422 + messages automatically)
        $data = $request->validate([
            'username' => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:registrations,email',
            'phone'    => 'required|string|max:30',
        ], [
            'email.unique' => 'This email is already registered.',
        ]);

        // Yes → INSERT registrations (token, username, email, phone)
        $registration = Registration::create([
            'username' => trim($data['username']),
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'token'    => (string) Str::uuid(),
        ]);

        // → 2. Confirmation email (sent_by = null = system)
        $sent = $this->mailer->send($registration, 'qr');

        return response()->json([
            'email_sent' => $sent,
            'message'    => $sent
                ? 'Registered! Your QR code is below and in your email.'
                : 'Registered, but the email could not be sent. Save the QR code below.',
            // the same QR as the email_logs type = 'qr' email, shown on the page
            'qr'         => 'data:image/png;base64,'.base64_encode($this->mailer->qrPng($registration)),
        ], 201);
    }

    // Admin pages: guest list + numbers
    public function index()
    {
        $rows = Registration::latest()->get();

        return response()->json([
            'stats' => [
                'total'      => $rows->count(),
                'emailed'    => $rows->whereNotNull('email_sent_at')->count(),
                'checked_in' => $rows->whereNotNull('checked_in_at')->count(),
            ],
            'data' => $rows,
        ]);
    }

    // Resend email → sent_by = admin id
    public function resend(Request $request, Registration $registration)
    {
        $sent = $this->mailer->send($registration, 'resend', $request->user()->id);

        return response()->json([
            'email_sent' => $sent,
            'message'    => $sent ? 'Email resent to '.$registration->email : 'Failed to send email',
        ]);
    }
}
