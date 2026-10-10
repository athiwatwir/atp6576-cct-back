<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginStudentRequest;
use App\Http\Requests\Api\RegisterStudentRequest;
use App\Http\Resources\Student\StudentResource;
use App\Models\Role;
use App\Models\User;
use App\Services\Mail\MailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    private const VERIFICATION_MINUTES = 60;

    public function __construct(private MailService $mail) {}

    public function register(RegisterStudentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = Role::query()->where('name', 'student')->firstOrFail();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'school_name' => $data['school_name'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'subdistrict' => $data['subdistrict'] ?? null,
            'district' => $data['district'] ?? null,
            'province' => $data['province'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'password' => $data['password'],
            'status' => AccountStatus::Active->value,
        ]);

        $user->roles()->attach($role->id);
        $this->sendVerificationLink($user);

        return response()->json([
            'message' => 'สมัครสมาชิกแล้ว เข้าสู่ระบบได้ทันที',
            'email' => $user->email,
        ], 201);
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

    public function verifyEmail(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->find($id);
        $valid = $request->hasValidSignature()
            && $user
            && $user->hasRole('student')
            && hash_equals(sha1((string) $user->email), $hash);

        if (! $valid) {
            return $this->verificationRedirect('invalid');
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $this->verificationRedirect('success');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'กรุณาระบุอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
        ], [
            'email' => 'อีเมล',
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user && $user->hasRole('student') && $user->email_verified_at === null && $user->status === AccountStatus::Active->value) {
            $this->sendVerificationLink($user);
        }

        return response()->json([
            'message' => 'ถ้าอีเมลนี้ยังไม่ยืนยัน ระบบได้ส่งลิงก์ยืนยันให้แล้ว',
        ]);
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

    private function sendVerificationLink(User $user): void
    {
        try {
            $this->mail->sendCustomerEmailVerification(
                to: $user,
                verifyUrl: $this->verificationUrl($user),
                expiresMinutes: self::VERIFICATION_MINUTES,
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'api.auth.email.verify',
            now()->addMinutes(self::VERIFICATION_MINUTES),
            [
                'id' => $user->id,
                'hash' => sha1((string) $user->email),
            ],
        );
    }

    private function verificationRedirect(string $status): RedirectResponse
    {
        return redirect()->away($this->studentAppUrl().'/verify-email?status='.$status);
    }

    private function studentAppUrl(): string
    {
        $explicit = config('services.storefront.app_url');
        $source = is_string($explicit) && $explicit !== ''
            ? $explicit
            : (string) config('services.storefront.frontend_url');

        foreach (explode(',', $source) as $origin) {
            $origin = trim($origin);

            if ($origin !== '' && $origin !== '*') {
                return rtrim($origin, '/');
            }
        }

        return rtrim((string) config('app.url'), '/');
    }
}
