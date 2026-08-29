<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Full access to the admin panel',
            ],
            [
                'name' => 'staff',
                'display_name' => 'Staff',
                'description' => 'Staff access to backend operations',
            ],
            [
                'name' => 'instructor',
                'display_name' => 'Instructor',
                'description' => 'Instructor access to course content',
            ],
            [
                'name' => 'student',
                'display_name' => 'Student',
                'description' => 'Student account — cannot access admin panel',
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
