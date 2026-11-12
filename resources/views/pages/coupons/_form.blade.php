@php
    /** @var \App\Models\Coupon|null $coupon */
    $coupon = $coupon ?? null;
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $labelClass = 'mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400';
    $helpClass = 'mt-1 text-[11px] text-gray-500 dark:text-gray-400';
    $sectionClass = 'rounded-xl border border-gray-200 p-4 dark:border-gray-800';

    $ids = fn (string $field, $relation) => array_map('strval', old($field, $relation?->pluck('id')->all() ?? []));
    $statusMeta = fn (?string $status, string $visible) => $status !== $visible ? \App\Enums\ContentStatus::labelFor($status) : null;
    $meta = fn (array $parts) => collect($parts)->filter(fn ($part) => filled($part))->implode(' · ');

    $courseItems = $courses->map(fn ($course) => [
        'id' => $course->id,
        'label' => $course->name,
        'meta' => $meta([$course->code, $statusMeta($course->status, 'published')]),
        'chip' => $course->code ? $course->name.' ('.$course->code.')' : $course->name,
    ])->all();
    $curriculumItems = $curriculums->map(fn ($curriculum) => [
        'id' => $curriculum->id,
        'label' => $curriculum->name,
        'meta' => $statusMeta($curriculum->status, 'published'),
    ])->all();
    $examItems = $exams->map(fn ($exam) => [
        'id' => $exam->id,
        'label' => $exam->title,
        'meta' => $statusMeta($exam->status, 'published'),
    ])->all();
    $bookItems = $books->map(fn ($book) => [
        'id' => $book->id,
        'label' => $book->name,
        'meta' => $meta(['฿'.number_format((float) ($book->sale_price ?? $book->price), 2), $statusMeta($book->status, 'active')]),
    ])->all();
    $videoItems = $videos->sortBy(fn ($video) => $video->is_free ? 0 : 1)->map(function ($video) use ($meta) {
        $courses = $video->chapters->map(fn ($chapter) => $chapter->course)->filter()->unique('id');
        $trialOpen = $courses->contains(fn ($course) => $course->is_trial_available);
        $limit = $video->trialLimitSeconds();

        return [
            'id' => $video->id,
            'label' => $video->title,
            'meta' => $meta([
                $video->duration_seconds > 0 ? gmdate($video->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', $video->duration_seconds) : null,
                $courses->pluck('name')->implode(', '),
            ]),
            'badge' => $video->is_free
                ? 'ทดลองเรียน · '.($limit ? 'ดูได้ '.\App\Models\Video::secondsLabel($limit) : 'ทั้งคลิป').($trialOpen ? '' : ' (วิชายังไม่เปิดทดลอง)')
                : null,
            'badgeTone' => $trialOpen ? 'success' : 'gray',
        ];
    })->values()->all();

    $studentGroup = old('student_group', $coupon?->studentGroup() ?? 'all');
    $purchaseType = old('purchase_type', $coupon?->purchaseType() ?? 'any');
    $discountType = old('discount_type', $coupon?->discount_type ?? 'percentage');
    $videoMinutes = old('required_video_minutes', $coupon?->required_video_seconds ? \App\Support\CourseVideoUpload::trialMinutesValue($coupon->required_video_seconds) : '');
@endphp

{{-- รหัสและสถานะ --}}
<div class="{{ $sectionClass }}">
    <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">ข้อมูลคูปอง</h4>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
        <div class="md:col-span-3">
            <label for="code" class="{{ $labelClass }}">รหัสคูปอง<span class="text-error-500">*</span></label>
            <input type="text" id="code" name="code" value="{{ old('code', $coupon?->code) }}" required maxlength="100" placeholder="เช่น NEWSTUDENT10" class="{{ $inputClass }} font-mono uppercase" />
            <p class="{{ $helpClass }}">นักเรียนกรอกรหัสนี้ตอนสั่งซื้อ ใช้ได้เฉพาะ A–Z ตัวเลข _ และ -</p>
            @error('code')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-3">
            <label for="status" class="{{ $labelClass }}">สถานะ<span class="text-error-500">*</span></label>
            <select id="status" name="status" required class="{{ $inputClass }}">
                @foreach (\App\Enums\ContentStatus::options(['active', 'inactive']) as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $coupon?->status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-6">
            <label for="description" class="{{ $labelClass }}">รายละเอียด / ชื่อแคมเปญ</label>
            <textarea id="description" name="description" rows="2" placeholder="เช่น ลด 10% สำหรับนักเรียนใหม่ เดือน พ.ย."
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $coupon?->description) }}</textarea>
        </div>
    </div>
</div>

{{-- ส่วนลดและการใช้ --}}
<div class="{{ $sectionClass }}" x-data="{ type: @js($discountType) }">
    <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">ส่วนลด จำนวนครั้ง และระยะเวลา</h4>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div>
            <label for="discount_type" class="{{ $labelClass }}">ประเภทส่วนลด<span class="text-error-500">*</span></label>
            <select id="discount_type" name="discount_type" required x-model="type" class="{{ $inputClass }}">
                @foreach (\App\Enums\DiscountType::options() as $value => $label)
                    <option value="{{ $value }}" @selected($discountType === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="discount_value" class="{{ $labelClass }}">มูลค่าส่วนลด</label>
            <div class="relative">
                <input type="number" id="discount_value" name="discount_value" min="0" step="0.01" placeholder="0 = แถมอย่างเดียว"
                    :max="type === 'percentage' ? 100 : null"
                    value="{{ old('discount_value', $coupon && (float) $coupon->discount_value > 0 ? $coupon->discount_value : '') }}" class="{{ $inputClass }} pr-9" />
                <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm text-gray-400" x-text="type === 'percentage' ? '%' : '฿'"></span>
            </div>
            @error('discount_value')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="max_discount_amount" class="{{ $labelClass }}">ส่วนลดสูงสุด (฿)</label>
            <input type="number" id="max_discount_amount" name="max_discount_amount" min="0" step="0.01" placeholder="ไม่จำกัด"
                :disabled="type !== 'percentage'"
                value="{{ old('max_discount_amount', $coupon?->max_discount_amount) }}" class="{{ $inputClass }} disabled:opacity-50" />
        </div>
        <div>
            <label for="min_purchase_amount" class="{{ $labelClass }}">ยอดขั้นต่ำ (฿)</label>
            <input type="number" id="min_purchase_amount" name="min_purchase_amount" min="0" step="0.01" placeholder="ไม่มี"
                value="{{ old('min_purchase_amount', $coupon?->min_purchase_amount) }}" class="{{ $inputClass }}" />
        </div>
        <div>
            <label for="usage_limit" class="{{ $labelClass }}">ใช้ได้ทั้งหมด (ครั้ง)</label>
            <input type="number" id="usage_limit" name="usage_limit" min="1" step="1" placeholder="ไม่จำกัด"
                value="{{ old('usage_limit', $coupon?->usage_limit) }}" class="{{ $inputClass }}" />
        </div>
        <div>
            <label for="per_user_limit" class="{{ $labelClass }}">ต่อคน (ครั้ง)</label>
            <input type="number" id="per_user_limit" name="per_user_limit" min="1" step="1" placeholder="ไม่จำกัด"
                value="{{ old('per_user_limit', $coupon?->per_user_limit) }}" class="{{ $inputClass }}" />
        </div>
        <div class="[&_label]:mb-1 [&_label]:text-xs [&_label]:text-gray-600">
            <x-form.date-picker id="start_at" name="start_at" label="วันเริ่ม" :enable-time="true" :value="old('start_at', $coupon?->start_at)" />
        </div>
        <div class="[&_label]:mb-1 [&_label]:text-xs [&_label]:text-gray-600">
            <x-form.date-picker id="end_at" name="end_at" label="วันสิ้นสุด" :enable-time="true" :value="old('end_at', $coupon?->end_at)" />
        </div>
    </div>
    <p class="{{ $helpClass }}">มูลค่าส่วนลดเว้นว่างหรือ 0 = คูปองแถมอย่างเดียว (ต้องเลือกของแถมด้านล่าง) · ส่วนลดสูงสุดใช้กับแบบเปอร์เซ็นต์ · ช่องตัวเลขอื่นเว้นว่างเท่ากับไม่จำกัด</p>
</div>

{{-- เงื่อนไขผู้ใช้ --}}
<div class="{{ $sectionClass }}">
    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">ใครใช้ได้</h4>
    <p class="{{ $helpClass }}">ระบบตรวจตอนนักเรียนกรอกรหัส ไม่ผ่านจะแจ้งเหตุผลและไม่ให้ส่วนลด</p>

    <div class="mt-3">
        <span class="{{ $labelClass }}">กลุ่มนักเรียน<span class="text-error-500">*</span></span>
        <div class="inline-flex rounded-lg border border-gray-300 p-0.5 dark:border-gray-700">
            @foreach (\App\Models\Coupon::STUDENT_GROUPS as $value => $label)
                <label class="cursor-pointer">
                    <input type="radio" name="student_group" value="{{ $value }}" class="peer sr-only" @checked($studentGroup === $value) required>
                    <span class="block rounded-md px-3 py-1.5 text-xs font-medium text-gray-600 peer-checked:bg-brand-500 peer-checked:text-white dark:text-gray-300">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <p class="{{ $helpClass }}">นักเรียนใหม่ = ยังไม่เคยชำระสำเร็จ · นักเรียนเก่า = เคยชำระสำเร็จอย่างน้อย 1 ครั้ง</p>
        @error('student_group')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
    </div>

    <div class="mt-4">
        <span class="{{ $labelClass }}">ประเภทการซื้อ<span class="text-error-500">*</span></span>
        <div class="inline-flex rounded-lg border border-gray-300 p-0.5 dark:border-gray-700">
            @foreach (\App\Models\Coupon::PURCHASE_TYPES as $value => $label)
                <label class="cursor-pointer">
                    <input type="radio" name="purchase_type" value="{{ $value }}" class="peer sr-only" @checked($purchaseType === $value) required>
                    <span class="block rounded-md px-3 py-1.5 text-xs font-medium text-gray-600 peer-checked:bg-brand-500 peer-checked:text-white dark:text-gray-300">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <p class="{{ $helpClass }}">ต่ออายุ = เคยมีสิทธิ์เรียนวิชา หลักสูตร หรือข้อสอบนั้นมาก่อน (ยังใช้ได้หรือหมดอายุแล้วก็ได้) · ส่วนลดคิดเฉพาะรายการในตะกร้าที่ตรงประเภท · หนังสือนับเป็นการซื้อใหม่เสมอ</p>
        @error('purchase_type')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
        <x-form.item-picker name="required_course_ids" label="ต้องเคยซื้อวิชา" :items="$courseItems"
            :selected="$ids('required_course_ids', $coupon?->requiredCourses)" empty-text="ไม่บังคับ" />
        <x-form.item-picker name="required_product_ids" label="ต้องเคยซื้อหนังสือ" :items="$bookItems"
            :selected="$ids('required_product_ids', $coupon?->requiredProducts)" empty-text="ไม่บังคับ"
            help="เลือกวิชาหรือหนังสือไว้ ต้องเคยซื้ออย่างน้อย 1 รายการจากที่เลือก" />

        <x-form.item-picker name="required_video_ids" label="ต้องดูวิดีโอก่อนใช้คูปอง" :items="$videoItems"
            :selected="$ids('required_video_ids', $coupon?->requiredVideos)" empty-text="ไม่ต้องดู"
            help="ต้องดูครบทุกคลิปที่เลือก คลิปทดลองเรียนอยู่ด้านบนของรายการ (คนที่ยังไม่ซื้อวิชาดูได้เฉพาะคลิปทดลอง และดูได้ไม่เกินเวลาทดลอง)" />
        <div>
            <label for="required_video_minutes" class="{{ $labelClass }}">ต้องดูอย่างน้อย (นาที)</label>
            <input type="number" id="required_video_minutes" name="required_video_minutes" min="0.5" max="1440" step="0.5"
                value="{{ $videoMinutes }}" placeholder="เว้นว่าง = ต้องดูจนจบ" class="{{ $inputClass }}" />
            <p class="{{ $helpClass }}">ใช้กับทุกวิดีโอที่เลือก ใส่ทศนิยมได้ เช่น 1.5 = 1 นาที 30 วินาที</p>
            @error('required_video_minutes')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

{{-- ของแถม --}}
<div class="{{ $sectionClass }}">
    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">ของแถม</h4>
    <p class="{{ $helpClass }}">ได้ของแถมหลังชำระเงินสำเร็จ ข้อสอบเปิดสิทธิ์ให้อัตโนมัติ หนังสือสร้างเป็นออเดอร์หนังสือยอด 0 บาทรอจัดส่ง</p>
    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
        <x-form.item-picker name="gift_course_ids" label="เมื่อซื้อวิชา" :items="$courseItems"
            :selected="$ids('gift_course_ids', $coupon?->giftCourses)" empty-text="ทุกออเดอร์ที่ใช้คูปอง"
            help="ออเดอร์ต้องมีวิชาที่เลือกอย่างน้อย 1 วิชา" />
        <x-form.item-picker name="gift_assessment_ids" label="แถมข้อสอบ" :items="$examItems"
            :selected="$ids('gift_assessment_ids', $coupon?->giftAssessments)" empty-text="ไม่แถม" />
        <x-form.item-picker name="gift_product_ids" label="แถมหนังสือ" :items="$bookItems"
            :selected="$ids('gift_product_ids', $coupon?->giftProducts)" empty-text="ไม่แถม" />
    </div>
</div>

{{-- สินค้าที่ใช้ส่วนลดได้ --}}
<div class="{{ $sectionClass }}">
    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">สินค้าที่ใช้ส่วนลดได้</h4>
    <p class="{{ $helpClass }}">ไม่เลือกเลย = ใช้ได้กับทุกรายการในออเดอร์ · เลือกอย่างน้อย 1 รายการ = คิดส่วนลดเฉพาะรายการที่เลือก</p>
    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
        <x-form.item-picker name="course_ids" label="วิชา" :items="$courseItems" :selected="$ids('course_ids', $coupon?->courses)" />
        <x-form.item-picker name="curriculum_ids" label="หลักสูตร" :items="$curriculumItems" :selected="$ids('curriculum_ids', $coupon?->curriculums)" />
        <x-form.item-picker name="assessment_ids" label="ข้อสอบขายแยก" :items="$examItems" :selected="$ids('assessment_ids', $coupon?->assessments)" />
        <x-form.item-picker name="product_ids" label="หนังสือ" :items="$bookItems" :selected="$ids('product_ids', $coupon?->products)" />
    </div>
</div>
