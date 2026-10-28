<?php

namespace App\Http\Controllers;

use App\Models\CheckinLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Users page: staff / admin accounts that can log in
class UserController extends Controller
{
    public function index()
    {
        return User::orderBy('id')->get();
    }

    // Add user → password saved as bcrypt hash by the User model
    public function store(Request $request)
    {
        $data = $request->validate([
            'fullname' => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ], [], ['fullname' => 'full name']);

        $user = User::create($data);

        return response()->json(['message' => 'User '.$user->email.' added.', 'data' => $user], 201);
    }

    // Edit user → empty password = keep the old one
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'fullname' => 'required|string|max:100',
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
        ], [], ['fullname' => 'full name']);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return response()->json(['message' => 'User '.$user->email.' saved.', 'data' => $user]);
    }

    // Delete user → never yourself, never the last account
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => "You can't delete your own account."], 422);
        }
        if (User::count() <= 1) {
            return response()->json(['message' => "You can't delete the last account."], 422);
        }

        $user->tokens()->delete(); // log them out everywhere
        CheckinLog::where('user_id', $user->id)->update(['user_id' => null]); // keep their scans
        $user->delete();

        return response()->json(['message' => 'User '.$user->email.' deleted.']);
    }
}
