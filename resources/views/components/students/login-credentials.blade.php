@props(['student'])

{{-- ดูรหัสผ่านเข้าสู่ระบบของนักเรียน (เปิดดู / สร้างใหม่ บันทึกใน Activity Log) --}}
<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 p-4 dark:border-gray-700']) }}
    x-data="{
        loading: false,
        login: null,
        message: null,
        canReset: false,
        copied: null,
        post(url) {
            this.loading = true;
            this.message = null;

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    if (response.ok) {
                        this.login = data;
                        this.message = data.message ?? null;
                    } else {
                        this.login = null;
                        this.message = data.message ?? 'ทำรายการไม่สำเร็จ กรุณาลองอีกครั้ง';
                        this.canReset = Boolean(data.can_reset);
                    }
                })
                .catch(() => this.message = 'เชื่อมต่อไม่สำเร็จ กรุณาลองอีกครั้ง')
                .finally(() => this.loading = false);
        },
        show() {
            return this.post(@js(route('students.login-password', $student)));
        },
        loginText() {
            return [
                'ข้อมูลเข้าสู่ระบบ ' + @js(config('app.name')),
                'ชื่อผู้ใช้: ' + [@js($student->email), @js($student->phone)].filter(Boolean).join(' หรือ '),
                'รหัสผ่าน: ' + this.login.password,
                'เข้าสู่ระบบที่: ' + this.login.login_url,
            ].join('\n');
        },
        async copyAll() {
            if (!this.login) await this.show();
            if (!this.login) return;
            this.copy('all', this.loginText());
        },
        reset() {
            if (!confirm('สร้างรหัสผ่านใหม่ให้นักเรียนคนนี้? รหัสเดิมจะใช้ไม่ได้ทันที และนักเรียนจะถูกออกจากระบบทุกอุปกรณ์')) return;
            this.post(@js(route('students.login-password.reset', $student)));
        },
        copy(key, text) {
            const done = () => {
                this.copied = key;
                setTimeout(() => this.copied = null, 2000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done).catch(() => this.copyFallback(text) && done());
            } else if (this.copyFallback(text)) {
                done();
            }
        },
        copyFallback(text) {
            const area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            const ok = document.execCommand('copy');
            area.remove();
            if (!ok) this.message = 'คัดลอกไม่สำเร็จ กรุณาคัดลอกเอง';
            return ok;
        },
    }">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">ข้อมูลเข้าสู่ระบบ</h4>
        <div class="flex gap-2">
            <button type="button" data-no-loading @click="copyAll()" :disabled="loading" title="คัดลอกชื่อผู้ใช้ รหัสผ่าน และลิงก์เข้าสู่ระบบ"
                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium disabled:opacity-50"
                :class="copied === 'all' ? 'border-success-300 bg-success-50 text-success-700 dark:border-success-500/40 dark:bg-success-500/10 dark:text-success-400' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5'">
                <svg x-show="copied !== 'all'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/></svg>
                <svg x-show="copied === 'all'" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>
                <span x-text="copied === 'all' ? 'คัดลอกแล้ว' : 'คัดลอกทั้งหมด'"></span>
            </button>
            <button type="button" data-no-loading x-show="!login" @click="show()" :disabled="loading"
                class="bg-brand-500 hover:bg-brand-600 inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium text-white disabled:opacity-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                <span x-text="loading ? 'กำลังโหลด...' : 'ดูรหัสผ่าน'"></span>
            </button>
            <button type="button" data-no-loading x-show="login" x-cloak @click="login = null; message = null"
                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">
                ซ่อน
            </button>
        </div>
    </div>

    <dl class="mt-3 space-y-2 text-sm">
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500">อีเมล</dt>
            <dd class="flex items-center gap-2 font-medium break-all text-gray-800 dark:text-white/90">
                {{ $student->email ?: '-' }}
                @if ($student->email)
                <button type="button" data-no-loading @click="copy('email', @js($student->email))" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400" x-text="copied === 'email' ? 'คัดลอกแล้ว' : 'คัดลอก'"></button>
                @endif
            </dd>
        </div>
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500">เบอร์โทร</dt>
            <dd class="flex items-center gap-2 font-medium text-gray-800 dark:text-white/90">
                {{ $student->phone ?: '-' }}
                @if ($student->phone)
                <button type="button" data-no-loading @click="copy('phone', @js($student->phone))" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400" x-text="copied === 'phone' ? 'คัดลอกแล้ว' : 'คัดลอก'"></button>
                @endif
            </dd>
        </div>
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500">รหัสผ่าน</dt>
            <dd class="flex items-center gap-2">
                <span x-show="!login" class="font-mono tracking-widest text-gray-400">••••••</span>
                <template x-if="login">
                    <span class="flex items-center gap-2">
                        <span class="rounded-md bg-brand-50 px-2 py-0.5 font-mono text-base font-semibold tracking-wider text-brand-700 dark:bg-brand-500/15 dark:text-brand-300" x-text="login.password"></span>
                        <button type="button" data-no-loading @click="copy('password', login.password)" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400" x-text="copied === 'password' ? 'คัดลอกแล้ว' : 'คัดลอก'"></button>
                    </span>
                </template>
            </dd>
        </div>
        <template x-if="login">
            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3" x-show="login.issued_at">
                    <dt class="text-gray-500">บันทึกรหัสเมื่อ</dt>
                    <dd class="text-gray-600 dark:text-gray-400" x-text="login.issued_at"></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-gray-500">หน้าเข้าสู่ระบบ</dt>
                    <dd><a :href="login.login_url" target="_blank" rel="noopener" class="text-xs font-medium break-all text-brand-600 hover:underline dark:text-brand-400" x-text="login.login_url"></a></dd>
                </div>
            </div>
        </template>
    </dl>

    <p x-show="message" x-cloak class="mt-3 rounded-lg px-3 py-2 text-xs"
        :class="login ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400'"
        x-text="message"></p>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3 dark:border-gray-800" x-show="login || canReset" x-cloak>
        <span class="text-xs text-gray-400">การเปิดดูและสร้างรหัสใหม่ถูกบันทึกใน Activity Log</span>
        <button type="button" data-no-loading @click="reset()" :disabled="loading"
            class="inline-flex items-center rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700 hover:bg-warning-100 disabled:opacity-50 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">
            สร้างรหัสผ่านใหม่
        </button>
    </div>
</div>
