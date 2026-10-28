<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens; // gives createToken() for login

    protected $fillable = ['fullname', 'email', 'password'];

    protected $hidden = ['password']; // never send the hash in API responses

    protected $casts = ['password' => 'hashed']; // auto-hash when saving

    // Filament admin panel (backend /admin): every account in users can log in
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    // Filament shows a name in the top bar → our column is fullname, not name
    public function getFilamentName(): string
    {
        return (string) $this->fullname;
    }
}
