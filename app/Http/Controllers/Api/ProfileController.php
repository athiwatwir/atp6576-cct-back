<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateStudentPasswordRequest;
use App\Http\Requests\Api\UpdateStudentProfileRequest;
use App\Http\Resources\Student\StudentResource;
use App\Support\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => StudentResource::make($request->user())->resolve(),
        ]);
    }

    public function update(UpdateStudentProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->only([
            'name',
            'nickname',
            'email',
            'phone',
            'school_name',
            'school_province',
            'address_line1',
            'subdistrict',
            'district',
            'province',
            'postal_code',
        ]);

        if ($request->hasFile('avatar')) {
            $path = MediaStorage::storeAvatar($request->file('avatar'), $user->id);

            if ($user->avatar && $user->avatar !== $path) {
                MediaStorage::delete($user->avatar);
            }

            $data['avatar'] = $path;
        }

        $user->fill($data);

        if ($request->hasFile('avatar')) {
            // path คงที่ ต้อง bump updated_at เพื่อ cache-bust บน avatar_url
            $user->updated_at = now();
        }

        $user->save();

        return response()->json([
            'data' => StudentResource::make($user->refresh())->resolve(),
        ]);
    }

    public function updatePassword(UpdateStudentPasswordRequest $request): JsonResponse
    {
        $request->user()->setVisiblePassword($request->string('password')->toString())->save();

        return response()->json([
            'message' => 'เปลี่ยนรหัสผ่านแล้ว',
        ]);
    }

    /**
     * รหัสผ่านปัจจุบันของนักเรียนเอง จากสำเนาเข้ารหัส (`users.issued_password`) เรียกเมื่อกดดูเท่านั้น ห้าม cache
     * null เมื่อยังไม่มีสำเนา เช่น บัญชีเดิมที่ยังไม่เข้าสู่ระบบด้วยรหัสผ่านหลังเปิดระบบนี้
     */
    public function revealPassword(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()
            ->json([
                'data' => [
                    'password' => $user->issuedPassword(),
                    'updated_at' => $user->issued_password_at?->toIso8601String(),
                ],
            ])
            ->header('Cache-Control', 'no-store, private');
    }
}
