<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates the first admin so you can sign in. Safe to run more than once:
     * it updates the same user instead of creating a duplicate.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');

        $user = User::firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => env('ADMIN_NAME', 'Admin'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe123!')),
            'is_admin' => true,
        ])->save();
    }
}