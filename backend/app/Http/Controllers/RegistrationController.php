<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\TicketType;
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

        // new guests get the General ticket (admin can change it on the Tickets page)
        $general = TicketType::where('name', 'General')->where('is_active', true)->first();

        // Yes → INSERT registrations (token, fullname, email, phone, ticket type)
        $registration = Registration::create([
            'fullname'       => trim($data['fullname']),
            'email'          => $data['email'],
            'phone'          => $data['phone'],
            'token'          => (string) Str::uuid(),
            'ticket_type_id' => $general && ! $general->isFull() ? $general->id : null,
        ]);

        // → 2. Confirmation email (sent_by = null = system)
        $sent = $this->mailer->send($registration, 'qr');

        return response()->json([
            'email_sent' => $sent,
            'message'    => $sent
                ? 'Congratulations! You have registered successfully. Your QR code has been sent to your email.'
                : 'Registered, but the email could not be sent. Save the QR code below.',
            'qr'         => 'data:image/png;base64,'.base64_encode($this->mailer->qrPng($registration)),
        ], 201);
    }

    // Admin pages: guest list + numbers
    public function index()
    {
        $rows = Registration::with('ticketType:id,name')->latest()->get();

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
            'ticket_type_id' => 'nullable|exists:ticket_types,id',
        ], [
            'email.unique' => 'This email is already registered.',
        ], [
            'fullname' => 'full name',
            'ticket_type_id' => 'ticket type',
        ]);

        // changing the ticket type → it must be switched on and not full
        $typeId = $data['ticket_type_id'] ?? null;
        if ($typeId && $typeId != $registration->ticket_type_id) {
            $type = TicketType::find($typeId);
            if (! $type->is_active || $type->isFull($registration->id)) {
                return response()->json(['errors' => ['ticket_type_id' => [$type->name.' is '.($type->is_active ? 'full' : 'switched off').'.']]], 422);
            }
        }

        $emailChanged = strcasecmp($data['email'], $registration->email) !== 0;

        $registration->update([
            'fullname'       => trim($data['fullname']),
            'email'          => $data['email'],
            'phone'          => $data['phone'],
            'ticket_type_id' => $typeId,
        ]);

        $message = 'Saved.';
        if ($emailChanged) {
            $sent    = $this->mailer->send($registration, 'resend', $request->user()->id);
            $message = $sent
                ? 'Saved. QR code sent to '.$registration->email
                : 'Saved, but the email to '.$registration->email.' could not be sent.';
        }

        return response()->json(['message' => $message, 'data' => $registration->fresh('ticketType')]);
    }

    // Delete guest → their email logs go too, scan logs keep the row without the guest
    public function destroy(Registration $registration)
    {
        $registration->delete();

        return response()->json(['message' => $registration->fullname.' deleted.']);
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