<?php

namespace App\Http\Controllers;

use App\Support\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function edit(): View
    {
        return view('pages.settings.system', [
            'title' => 'ตั้งค่าระบบ',
            'notificationEmails' => old(
                'notification_emails',
                SystemSettings::notificationEmailsText()
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $raw = (string) $request->input('notification_emails', '');
        $emails = SystemSettings::parseEmails($raw);

        $tokens = collect(preg_split('/[\s,;]+/u', $raw) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        $invalid = $tokens
            ->reject(fn ($email) => (bool) filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'notification_emails' => 'อีเมลไม่ถูกต้อง: '.implode(', ', $invalid),
            ]);
        }

        SystemSettings::put(
            SystemSettings::NOTIFICATION_EMAILS,
            $emails === [] ? null : implode("\n", $emails)
        );

        return redirect()
            ->route('settings.system.edit')
            ->with('success', 'บันทึกตั้งค่าระบบเรียบร้อยแล้ว');
    }
}
