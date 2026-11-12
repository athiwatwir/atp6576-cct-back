<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->select(['id', 'phone'])
            ->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    $phone = Phone::normalize($user->phone);

                    if ($phone !== $user->phone) {
                        DB::table('users')->where('id', $user->id)->update(['phone' => $phone]);
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }
};
