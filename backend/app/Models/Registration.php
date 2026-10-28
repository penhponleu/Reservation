<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = ['ticket_no', 'fullname', 'email', 'phone', 'token', 'email_sent_at', 'checked_in_at'];

    protected $hidden = ['token']; // never send the QR value in API responses

    protected $casts = [
        'email_sent_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    // User registers → ticket created from the new id (id 7 → TKT-000007)
    protected static function booted(): void
    {
        static::created(function (Registration $registration) {
            $registration->ticket_no = sprintf('TKT-%06d', $registration->id);
            $registration->saveQuietly();
        });
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function checkinLogs()
    {
        return $this->hasMany(CheckinLog::class);
    }
}
