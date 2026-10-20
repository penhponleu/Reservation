<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckinLog extends Model
{
    public $timestamps = false; // only scanned_at

    protected $fillable = ['registration_id', 'user_id', 'result', 'scanned_value', 'scanned_at'];

    protected $casts = ['scanned_at' => 'datetime'];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
