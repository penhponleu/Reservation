<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

// personal_access_tokens without the abilities column: every admin token can do everything
class PersonalAccessToken extends SanctumToken
{
    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    protected $fillable = ['name', 'token', 'expires_at']; // abilities left out → not inserted

    public function can($ability)
    {
        return true;
    }

    public function cant($ability)
    {
        return false;
    }
}
