<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ConfirmationMail extends Mailable
{
    public function __construct(
        public Registration $registration,
        public string $qrPng, // QR image bytes
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Registration confirmed - your QR code');
    }

    public function content(): Content
    {
        return new Content(view: 'email.confirmation');
    }
}
