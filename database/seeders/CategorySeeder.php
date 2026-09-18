<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'ประถมศึกษา', 'slug' => 'primary', 'sort_order' => 1],
            ['name' => 'มัธยมศึกษาตอนต้น', 'slug' => 'lower-secondary', 'sort_order' => 2],
            ['name' => 'มัธยมศึกษาตอนปลาย', 'slug' => 'upper-secondary', 'sort_order' => 3],
            ['name' => 'เตรียมสอบเข้า', 'slug' => 'entrance-prep', 'sort_order' => 4],
        ];

        foreach ($categories as $item) {
            Category::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'status' => 'active',
                    'sort_order' => $item['sort_order'],
                ],
            );
        }
    }
}
