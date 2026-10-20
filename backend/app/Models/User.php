<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens; // gives createToken() for login

    protected $fillable = ['username', 'email', 'password'];

    protected $hidden = ['password']; // never send the hash in API responses

    protected $casts = ['password' => 'hashed']; // auto-hash when saving
}
