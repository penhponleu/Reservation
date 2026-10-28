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
            'fullname' => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:registrations,email',
            'phone'    => 'required|string|max:30',
        ], [
            'email.unique' => 'This email is already registered.',
        ], [
            'fullname' => 'full name', // error text says "full name"
        ]);

        // Yes → INSERT registrations (token, fullname, email, phone)
        $registration = Registration::create([
            'fullname' => trim($data['fullname']),
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'token'    => (string) Str::uuid(),
        ]);

        // → 2. Confirmation email (sent_by = null = system)
        $sent = $this->mailer->send($registration, 'qr');

        return response()->json([
            'email_sent' => $sent,
            'message'    => $sent
                ? 'Registered! Your QR code has been sent to your email.'
                : 'Registered, but the email could not be sent. Save the QR code below.',
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

    // Admin fixes a guest's name / email / phone → new email? send the QR there
    public function update(Request $request, Registration $registration)
    {
        $data = $request->validate([
            'fullname' => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:registrations,email,'.$registration->id,
            'phone'    => 'required|string|max:30',
        ], [
            'email.unique' => 'This email is already registered.',
        ], [
            'fullname' => 'full name',
        ]);

        $emailChanged = strcasecmp($data['email'], $registration->email) !== 0;

        $registration->update([
            'fullname' => trim($data['fullname']),
            'email'    => $data['email'],
            'phone'    => $data['phone'],
        ]);

        $message = 'Saved.';
        if ($emailChanged) {
            $sent    = $this->mailer->send($registration, 'resend', $request->user()->id);
            $message = $sent
                ? 'Saved. QR code sent to '.$registration->email
                : 'Saved, but the email to '.$registration->email.' could not be sent.';
        }

        return response()->json(['message' => $message, 'data' => $registration->fresh()]);
    }

    // Admin clicks "Get ticket" → save a ticket number (id 1 → TKT-000001)
    public function ticket(Registration $registration)
    {
        if (! $registration->ticket_no) {
            $registration->update(['ticket_no' => sprintf('TKT-%06d', $registration->id)]);
        }

        return response()->json([
            'message' => 'Ticket '.$registration->ticket_no.' for '.$registration->fullname,
            'data'    => $registration,
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