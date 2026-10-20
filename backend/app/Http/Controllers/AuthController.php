<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Admin logs in → SELECT users by email, check hashed password
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        $stored = trim((string) $user?->password); // phpMyAdmin Enter adds "\r\n"

        // Password typed as plain text in phpMyAdmin → compare as text instead of crashing
        $plain = $stored !== '' && ! Hash::isHashed($stored);
        $ok = $stored !== '' && ($plain
            ? hash_equals($stored, $data['password'])
            : Hash::check($data['password'], $stored));

        // Correct? No → "Wrong email or password"
        if (! $ok) {
            return response()->json(['message' => 'Wrong email or password'], 401);
        }

        // Plain text matched → save it as a bcrypt hash from now on
        if ($plain) {
            $user->update(['password' => $data['password']]);
        }

        // Yes → INSERT personal_access_tokens
        $token = $user->createToken('admin')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    // Logout → DELETE personal_access_tokens
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
