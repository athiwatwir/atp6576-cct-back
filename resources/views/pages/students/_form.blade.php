@php
$inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
$labelClass = 'mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400';
$sectionClass = 'mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase dark:text-gray-500';
$contactError = $errors->first('email') ?: $errors->first('phone');
@endphp

<div class="space-y-5">
    <section>
        <p class="{{ $sectionClass }}">ข้อมูลนักเรียน</p>
        <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-3">
            <div>
                <label for="name" class="{{ $labelClass }}">ชื่อ<span class="text-error-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $student?->name) }}" required class="{{ $inputClass }}" />
                @error('name')
                <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="{{ $labelClass }}">อีเมล</label>
                <input type="email" id="email" name="email" value="{{ old('email', $student?->email) }}" placeholder="name@example.com" class="{{ $inputClass }}" />
            </div>

            <div>
                <label for="phone" class="{{ $labelClass }}">เบอร์โทร</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $student?->phone) }}" placeholder="0812345678" class="{{ $inputClass }}" />
            </div>

            <p class="-mt-1 text-xs md:col-span-3 {{ $contactError ? 'text-error-500' : 'text-error-500 dark:text-gray-400' }}">
                {{ $contactError ?: 'กรอกอีเมลหรือเบอร์โทรอย่างน้อยหนึ่งอย่าง ใช้เป็นชื่อเข้าสู่ระบบ ถ้าไม่มีอีเมล ระบบจะไม่ส่งอีเมลผลการซื้อและรหัสผ่าน' }}
            </p>

            <div class="md:col-span-2">
                <label for="school_name" class="{{ $labelClass }}">ชื่อโรงเรียน</label>
                <input type="text" id="school_name" name="school_name" value="{{ old('school_name', $student?->school_name) }}" class="{{ $inputClass }}" />
                @error('school_name')
                <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="school_province" class="{{ $labelClass }}">จังหวัดที่เรียน</label>
                <input type="text" id="school_province" name="school_province" list="school-province-options" autocomplete="off" value="{{ old('school_province', $student?->school_province) }}" placeholder="พิมพ์หรือเลือกจังหวัด" class="{{ $inputClass }}" />
                <datalist id="school-province-options">
                    @foreach (\App\Support\ThaiProvinces::all() as $province)
                    <option value="{{ $province }}"></option>
                    @endforeach
                </datalist>
                @error('school_province')
                <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section>
        <p class="{{ $sectionClass }}">ที่อยู่ <span class="font-normal normal-case tracking-normal">· พิมพ์ตำบล อำเภอ หรือรหัสไปรษณีย์เพื่อให้ระบบเติมให้</span></p>
        <x-common.thai-address prefix="" :required="false" :compact="true" :address-line1="$student?->address_line1" :subdistrict="$student?->subdistrict" :district="$student?->district" :province="$student?->province" :postal-code="$student?->postal_code" />
    </section>

    <section>
        <p class="{{ $sectionClass }}">บัญชี</p>
        <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-3">
            <div>
                <label for="status" class="{{ $labelClass }}">สถานะ<span class="text-error-500">*</span></label>
                <select id="status" name="status" required class="{{ $inputClass }}">
                    @foreach (\App\Enums\AccountStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $student?->status ?? 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2" x-data="{
                    password: @js(old('password', '')),
                    generate() {
                        const letters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
                        const random = new Uint32Array(6);
                        crypto.getRandomValues(random);
                        this.password = letters[random[0] % letters.length]
                            + Array.from(random.slice(1), (value) => value % 10).join('');
                    },
                }">
                <label for="password" class="{{ $labelClass }}">{{ $student ? 'รหัสผ่านใหม่' : 'รหัสผ่าน' }}</label>
                <div class="flex gap-2">
                    <input type="text" id="password" name="password" x-model="password" autocomplete="new-password" spellcheck="false"
                        placeholder="{{ $student ? 'เว้นว่างถ้าไม่เปลี่ยน' : 'เว้นว่างได้ หรือกดสุ่มรหัส' }}" class="{{ $inputClass }} font-mono" />
                    <button type="button" data-no-loading @click="generate()"
                        class="inline-flex h-10 shrink-0 items-center rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        สุ่มรหัส
                    </button>
                </div>
                @error('password')
                <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <p class="-mt-1 text-xs text-gray-500 md:col-span-3 dark:text-gray-400">
                @if ($student)
                อย่างน้อย 6 ตัวอักษร เปลี่ยนรหัสผ่านจากหน้านี้ระบบจะไม่ส่งอีเมลแจ้ง ดูรหัสที่ตั้งไว้ได้ที่กล่องข้อมูลเข้าสู่ระบบในหน้านักเรียน
                @else
                อย่างน้อย 6 ตัวอักษร ถ้าเว้นว่าง ระบบจะออกรหัสผ่านให้ตอนชำระเงินครั้งแรก ถ้าตั้งไว้ ระบบใช้รหัสนี้และส่งไปในอีเมลผลการซื้อ (ถ้ามีอีเมล)
                @endif
            </p>
        </div>
    </section>
</div>

@push('scripts')
@include('partials.thai-address-scripts')
@endpush
