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
        $data = $request->safe()->only(['name', 'email', 'phone']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = MediaStorage::store($request->file('avatar'), 'avatars/'.$user->id);
        }

        $user->update($data);

        return response()->json([
            'data' => StudentResource::make($user->refresh())->resolve(),
        ]);
    }

    public function updatePassword(UpdateStudentPasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => $request->string('password')->toString(),
        ]);

        return response()->json([
            'message' => 'เปลี่ยนรหัสผ่านแล้ว',
        ]);
    }
}
