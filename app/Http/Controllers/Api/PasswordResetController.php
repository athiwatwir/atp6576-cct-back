<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Mail\MailService;
use App\Support\Phone;
use App\Support\StudentApp;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * ลืมรหัสผ่านของนักเรียน: ส่งลิงก์ไปที่อีเมลของบัญชี แล้วตั้งรหัสผ่านใหม่จากหน้า /reset-password ของเว็บนักเรียน
 */
class PasswordResetController extends Controller
{
    public function __construct(private MailService $mail) {}

    /**
     * ตอบข้อความเดียวกันเสมอ ไม่บอกว่ามีบัญชีหรือไม่
     */
    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
        ], [
            'login.required' => 'กรุณากรอกอีเมลหรือเบอร์โทร',
        ]);

        $broker = $this->broker();
        $minutes = (int) config('auth.passwords.users.expire', 60);

        foreach ($this->findStudents(trim($data['login'])) as $user) {
            if (blank($user->email) || $broker->getRepository()->recentlyCreatedToken($user)) {
                continue;
            }

            $token = $broker->createToken($user);
            $url = StudentApp::url('reset-password').'?'.http_build_query(['token' => $token, 'email' => $user->email]);

            try {
                $this->mail->sendCustomerForgotPassword($user, $url, $minutes);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'message' => 'ถ้ามีบัญชีที่ตรงกับข้อมูลนี้ เราได้ส่งลิงก์ตั้งรหัสผ่านใหม่ไปที่อีเมลของบัญชีแล้ว ลิงก์ใช้ได้ '.$minutes.' นาที',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'token.required' => 'ลิงก์ตั้งรหัสผ่านไม่ถูกต้อง',
            'email.required' => 'ลิงก์ตั้งรหัสผ่านไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.confirmed' => 'รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน',
        ], [
            'password' => 'รหัสผ่านใหม่',
        ]);

        $student = User::query()->students()->where('email', $data['email'])->first();

        if (! $student || $student->status !== AccountStatus::Active->value) {
            return $this->invalidLink();
        }

        $status = $this->broker()->reset($data, function (User $user, string $password) {
            $user->setVisiblePassword($password)
                ->forceFill(['remember_token' => Str::random(60)])
                ->save();

            // ออกจากระบบทุกอุปกรณ์ เผื่อมีคนอื่นถือ token เดิมอยู่
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return $this->invalidLink();
        }

        return response()->json([
            'message' => 'ตั้งรหัสผ่านใหม่แล้ว เข้าสู่ระบบด้วยรหัสผ่านใหม่ได้เลย',
            'email' => $student->email,
        ]);
    }

    /**
     * มี @ ถือเป็นอีเมล นอกนั้นถือเป็นเบอร์โทร (เบอร์เดียวอาจผูกหลายบัญชีจากข้อมูลเดิม)
     *
     * @return iterable<User>
     */
    private function findStudents(string $identifier): iterable
    {
        $query = User::query()->students()->where('status', AccountStatus::Active->value);

        if (str_contains($identifier, '@')) {
            return $query->where('email', $identifier)->limit(1)->get();
        }

        if (! Phone::looksLikePhone($identifier)) {
            return [];
        }

        return $query->where('phone', Phone::normalize($identifier))->limit(5)->get();
    }

    private function invalidLink(): JsonResponse
    {
        return response()->json([
            'message' => 'ลิงก์ตั้งรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว กรุณาขอลิงก์ใหม่',
            'errors' => ['token' => ['ลิงก์ตั้งรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว กรุณาขอลิงก์ใหม่']],
        ], 422);
    }

    private function broker(): PasswordBroker
    {
        /** @var PasswordBroker */
        return Password::broker('users');
    }
}
