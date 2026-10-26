<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Admin account for the app (not the database login).
    // Runs on every deploy: creates the admin once, never duplicates or resets it.
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@gmail.com')],
            [
                'fullname' => env('ADMIN_NAME', 'Admin'),
                'password' => env('ADMIN_PASSWORD', '123'), // hashed by the User model
            ],
        );
    }
}
