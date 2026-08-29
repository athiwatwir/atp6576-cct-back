<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        $studentRole = Role::query()->where('name', 'student')->firstOrFail();

        $admin = User::query()->updateOrCreate(
            ['email' => 'athiwat.wir@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Korn@001'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $student = User::query()->updateOrCreate(
            ['email' => 'student@clickclasstutor.com'],
            [
                'name' => 'Student Demo',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $student->roles()->sync([$studentRole->id]);
    }
}
