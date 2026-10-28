<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    // Admin logs in → find user by email → check password → give token
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
         
        ]);


        $user     = User::where('email', $data['email'])->first();
        $password = trim((string) $user?->password); // phpMyAdmin can add "\r\n"

        // password typed as plain text in phpMyAdmin → compare as text
        $plain = $password !== '' && ! Hash::isHashed($password);
        $ok    = $plain
            ? hash_equals($password, $data['password'])
            : Hash::check($data['password'], $password);

        if (! $ok) {
            return response()->json(['message' => 'Wrong email or password'], 401);
        }

        // plain text matched → save as bcrypt hash from now on
        if ($plain) {
            $user->update(['password' => $data['password']]);
        }

        $token = $user->createToken('admin')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    // Settings page → change my name / email / password (current password needed for a new one)
    public function updateMe(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'fullname'         => 'required|string|max:100',
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => 'required_with:password|nullable|string',
            'password'         => 'nullable|string|min:6|confirmed',
        ], [], ['fullname' => 'full name']);

        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'], $user->password)) {
                return response()->json(['errors' => ['current_password' => ['Current password is wrong.']]], 422);
            }
            $user->password = $data['password'];
        }
        $user->fill(['fullname' => $data['fullname'], 'email' => $data['email']])->save();

        return response()->json(['message' => 'Settings saved.', 'data' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
