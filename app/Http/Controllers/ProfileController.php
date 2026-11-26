<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\Mail\MailService;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly MailService $mailService,
    ) {}

    public function edit(): View
    {
        $user = Auth::user()->load('roles');

        return view('pages.profile', [
            'title' => 'โปรไฟล์',
            'user' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()
            ->route('profile.edit')
            ->with('success', 'บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ], [], [
            'avatar' => 'รูปโปรไฟล์',
        ]);

        $user = $request->user();
        $path = MediaStorage::storeAvatar($data['avatar'], $user->id);

        if ($user->avatar && $user->avatar !== $path) {
            MediaStorage::delete($user->avatar);
        }

        // path คงที่ (avatars/{id}/{id}.webp) ต้อง touch เพื่อให้ ?v= บน URL เปลี่ยน ไม่งั้น CDN/เบราว์เซอร์โชว์รูปเก่า
        $user->forceFill([
            'avatar' => $path,
            'updated_at' => now(),
        ])->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'เปลี่ยนรูปโปรไฟล์เรียบร้อยแล้ว');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $password = $request->validated('password');

        $user->update([
            'password' => $password,
        ]);

        $this->mailService->sendStaffNotification(
            to: $user,
            subject: 'รหัสผ่านของคุณถูกเปลี่ยนแล้ว',
            message: "มีการเปลี่ยนรหัสผ่านบัญชีหลังบ้านของคุณสำเร็จแล้ว\nหากไม่ได้เป็นผู้ดำเนินการ โปรดติดต่อผู้ดูแลระบบทันที",
            actionUrl: route('login'),
            actionText: 'เข้าสู่ระบบ',
            queue: false,
        );

        return redirect()
            ->route('profile.edit')
            ->with('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    }
}
