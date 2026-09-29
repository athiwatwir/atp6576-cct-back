<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginStudentRequest;
use App\Http\Requests\Api\RegisterStudentRequest;
use App\Http\Resources\Student\StudentResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterStudentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = Role::query()->where('name', 'student')->firstOrFail();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => AccountStatus::Active->value,
        ]);

        $user->roles()->attach($role->id);

        return $this->tokenResponse($user, 201);
    }

    public function login(LoginStudentRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $user || ! $user->password || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json([
                'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
            ], 401);
        }

        if (! $user->hasRole('student')) {
            return response()->json([
                'message' => 'บัญชีนี้ใช้กับระบบนักเรียนไม่ได้',
            ], 403);
        }

        if ($user->status !== AccountStatus::Active->value) {
            return response()->json([
                'message' => 'บัญชีนี้ถูกปิดใช้งาน',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        return $this->tokenResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'ออกจากระบบแล้ว',
        ]);
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('student')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => StudentResource::make($user)->resolve(),
        ], $status);
    }
}
