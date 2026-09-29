@php
/** @var \App\Models\Coupon|null $coupon */
$coupon = $coupon ?? null;
$inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
$selectedCourses = array_map('strval', old('course_ids', $coupon?->courses->pluck('id')->all() ?? []));
$selectedCurriculums = array_map('strval', old('curriculum_ids', $coupon?->curriculums->pluck('id')->all() ?? []));
$selectedExams = array_map('strval', old('assessment_ids', $coupon?->assessments->pluck('id')->all() ?? []));
$selectedBooks = array_map('strval', old('product_ids', $coupon?->products->pluck('id')->all() ?? []));
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-3">
    <div>
        <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รหัสคูปอง<span class="text-error-500">*</span></label>
        <input type="text" id="code" name="code" value="{{ old('code', $coupon?->code) }}" required maxlength="100" class="{{ $inputClass }} uppercase" />
        @error('code')<p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะ<span class="text-error-500">*</span></label>
        <select id="status" name="status" required class="{{ $inputClass }}">
            @foreach (\App\Enums\ContentStatus::options(['active', 'inactive']) as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $coupon?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="discount_type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ประเภทส่วนลด<span class="text-error-500">*</span></label>
        <select id="discount_type" name="discount_type" required class="{{ $inputClass }}">
            @foreach (\App\Enums\DiscountType::options() as $value => $label)
            <option value="{{ $value }}" @selected(old('discount_type', $coupon?->discount_type ?? 'percentage') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="discount_value" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">มูลค่าส่วนลด<span class="text-error-500">*</span></label>
        <input type="number" id="discount_value" name="discount_value" min="0.01" step="0.01" required value="{{ old('discount_value', $coupon?->discount_value) }}" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เปอร์เซ็นต์ใส่ 1–100 หรือจำนวนเงินเป็นบาท</p>
        @error('discount_value')<p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="max_discount_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ส่วนลดสูงสุด (บาท)</label>
        <input type="number" id="max_discount_amount" name="max_discount_amount" min="0" step="0.01" value="{{ old('max_discount_amount', $coupon?->max_discount_amount) }}" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ใช้กับส่วนลดแบบเปอร์เซ็นต์ เว้นว่างถ้าไม่จำกัด</p>
    </div>

    <div>
        <label for="min_purchase_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ยอดขั้นต่ำ (บาท)</label>
        <input type="number" id="min_purchase_amount" name="min_purchase_amount" min="0" step="0.01" value="{{ old('min_purchase_amount', $coupon?->min_purchase_amount) }}" class="{{ $inputClass }}" />
    </div>

    <div>
        <label for="usage_limit" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">จำนวนครั้งของรหัสนี้</label>
        <input type="number" id="usage_limit" name="usage_limit" min="1" step="1" value="{{ old('usage_limit', $coupon?->usage_limit) }}" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เว้นว่างถ้าไม่จำกัด</p>
    </div>

    <div>
        <label for="per_user_limit" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">จำกัดต่อคน</label>
        <input type="number" id="per_user_limit" name="per_user_limit" min="1" step="1" value="{{ old('per_user_limit', $coupon?->per_user_limit) }}" class="{{ $inputClass }}" />
    </div>

    <x-form.date-picker id="start_at" name="start_at" label="วันเริ่ม" :enable-time="true" :value="old('start_at', $coupon?->start_at)" />

    <x-form.date-picker id="end_at" name="end_at" label="วันสิ้นสุด" :enable-time="true" :value="old('end_at', $coupon?->end_at)" />

    <div class="md:col-span-2">
        <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รายละเอียด</label>
        <textarea id="description" name="description" rows="3" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $coupon?->description) }}</textarea>
    </div>
</div>

<div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">สินค้าที่ใช้รหัสนี้ได้</h4>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ไม่เลือกเลย เท่ากับใช้ได้กับทุกรายการในออเดอร์ ถ้าเลือกอย่างน้อย 1 รายการ ส่วนลดจะคิดเฉพาะรายการที่เลือก</p>
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="course_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">คอร์ส</label>
            <select id="course_ids" name="course_ids[]" multiple class="{{ $inputClass }} h-40">
                @foreach ($courses as $course)
                <option value="{{ $course->id }}" @selected(in_array((string) $course->id, $selectedCourses, true))>{{ $course->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="curriculum_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หลักสูตร</label>
            <select id="curriculum_ids" name="curriculum_ids[]" multiple class="{{ $inputClass }} h-40">
                @foreach ($curriculums as $curriculum)
                <option value="{{ $curriculum->id }}" @selected(in_array((string) $curriculum->id, $selectedCurriculums, true))>{{ $curriculum->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="assessment_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ข้อสอบขายแยก</label>
            <select id="assessment_ids" name="assessment_ids[]" multiple class="{{ $inputClass }} h-40">
                @foreach ($exams as $exam)
                <option value="{{ $exam->id }}" @selected(in_array((string) $exam->id, $selectedExams, true))>{{ $exam->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="product_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หนังสือ</label>
            <select id="product_ids" name="product_ids[]" multiple class="{{ $inputClass }} h-40">
                @foreach ($books as $book)
                <option value="{{ $book->id }}" @selected(in_array((string) $book->id, $selectedBooks, true))>{{ $book->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
