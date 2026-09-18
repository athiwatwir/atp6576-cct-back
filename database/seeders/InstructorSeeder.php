<?php

namespace Database\Seeders;

use App\Models\Instructor;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        Instructor::query()->updateOrCreate(
            ['slug' => 'default-instructor'],
            [
                'name' => 'ครูตัวอย่าง',
                'bio' => 'ผู้สอนเริ่มต้นสำหรับระบบ Click Class Tutor',
                'status' => 'active',
            ],
        );
    }
}
