@php
    /** @var \App\Models\Promotion|null $promotion */
    $promotion = $promotion ?? null;
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $selectedCourses = array_map('strval', old('course_ids', $promotion?->courses->pluck('id')->all() ?? []));
    $selectedCurriculums = array_map('strval', old('curriculum_ids', $promotion?->curriculums->pluck('id')->all() ?? []));
    $selectedExams = array_map('strval', old('assessment_ids', $promotion?->assessments->pluck('id')->all() ?? []));
    $selectedBooks = array_map('strval', old('product_ids', $promotion?->products->pluck('id')->all() ?? []));
    $selectedRequiredCourses = array_map('strval', old('required_course_ids', $promotion?->requiredCourses->pluck('id')->all() ?? []));
    $selectedRequiredBooks = array_map('strval', old('required_product_ids', $promotion?->requiredProducts->pluck('id')->all() ?? []));
    $selectedRequiredVideos = array_map('strval', old('required_video_ids', $promotion?->requiredVideos->pluck('id')->all() ?? []));
    $selectedGiftCourses = array_map('strval', old('gift_course_ids', $promotion?->giftCourses->pluck('id')->all() ?? []));
    $selectedGiftExams = array_map('strval', old('gift_assessment_ids', $promotion?->giftAssessments->pluck('id')->all() ?? []));
    $selectedGiftBooks = array_map('strval', old('gift_product_ids', $promotion?->giftProducts->pluck('id')->all() ?? []));
    $studentGroup = old('student_group', $promotion?->studentGroup() ?? 'all');
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ชื่อโปรโมชัน<span class="text-error-500">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $promotion?->name) }}" required class="{{ $inputClass }}" />
        @error('name')<p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="discount_type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ประเภทส่วนลด<span class="text-error-500">*</span></label>
        <select id="discount_type" name="discount_type" required class="{{ $inputClass }}">
            @foreach (\App\Enums\DiscountType::options() as $value => $label)
                <option value="{{ $value }}" @selected(old('discount_type', $promotion?->discount_type ?? 'percentage') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="discount_value" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">มูลค่าส่วนลด<span class="text-error-500">*</span></label>
        <input type="number" id="discount_value" name="discount_value" min="0.01" step="0.01" required value="{{ old('discount_value', $promotion?->discount_value) }}" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เปอร์เซ็นต์ใส่ 1–100 หรือจำนวนเงินเป็นบาท</p>
        @error('discount_value')<p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="max_discount_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ส่วนลดสูงสุด (บาท)</label>
        <input type="number" id="max_discount_amount" name="max_discount_amount" min="0" step="0.01" value="{{ old('max_discount_amount', $promotion?->max_discount_amount) }}" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ใช้กับส่วนลดแบบเปอร์เซ็นต์ เว้นว่างถ้าไม่จำกัด</p>
    </div>

    <div>
        <label for="min_purchase_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ยอดขั้นต่ำ (บาท)</label>
        <input type="number" id="min_purchase_amount" name="min_purchase_amount" min="0" step="0.01" value="{{ old('min_purchase_amount', $promotion?->min_purchase_amount) }}" class="{{ $inputClass }}" />
    </div>

    <div>
        <label for="usage_limit" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">จำนวนครั้งของโปรโมชัน</label>
        <input type="number" id="usage_limit" name="usage_limit" min="1" step="1" value="{{ old('usage_limit', $promotion?->usage_limit) }}" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เว้นว่างถ้าไม่จำกัด รวมทุกคูปองในโปรโมชันนี้</p>
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะ<span class="text-error-500">*</span></label>
        <select id="status" name="status" required class="{{ $inputClass }}">
            @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $promotion?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <x-form.date-picker id="start_at" name="start_at" label="วันเริ่ม" :enable-time="true" :value="old('start_at', $promotion?->start_at)" />

    <x-form.date-picker id="end_at" name="end_at" label="วันสิ้นสุด" :enable-time="true" :value="old('end_at', $promotion?->end_at)" />

    <div class="md:col-span-2">
        <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รายละเอียด</label>
        <textarea id="description" name="description" rows="3" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $promotion?->description) }}</textarea>
    </div>
</div>

<div class="space-y-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
    <div>
        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">เงื่อนไขโปรโมชัน</h4>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">กำหนดว่าใครใช้ได้ ต้องเคยซื้อหรือดูอะไรก่อน และซื้อแล้วได้อะไรแถม</p>
    </div>

    <div>
        <label for="student_group" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">กลุ่มนักเรียน<span class="text-error-500">*</span></label>
        <select id="student_group" name="student_group" required class="{{ $inputClass }}">
            <option value="all" @selected($studentGroup === 'all')>ทุกคน</option>
            <option value="new" @selected($studentGroup === 'new')>นักเรียนใหม่</option>
            <option value="returning" @selected($studentGroup === 'returning')>นักเรียนเก่า</option>
        </select>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">นักเรียนใหม่คือยังไม่เคยชำระสำเร็จ นักเรียนเก่าคือเคยชำระสำเร็จอย่างน้อย 1 ครั้ง</p>
        @error('student_group')<p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="required_course_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ต้องเคยซื้อคอร์ส</label>
            <select id="required_course_ids" name="required_course_ids[]" multiple class="{{ $inputClass }} h-36">
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected(in_array((string) $course->id, $selectedRequiredCourses, true))>{{ $course->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="required_product_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ต้องเคยซื้อหนังสือ</label>
            <select id="required_product_ids" name="required_product_ids[]" multiple class="{{ $inputClass }} h-36">
                @foreach ($books as $book)
                    <option value="{{ $book->id }}" @selected(in_array((string) $book->id, $selectedRequiredBooks, true))>{{ $book->name }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ไม่เลือกทั้งสองช่อง เท่ากับไม่บังคับว่าต้องเคยซื้อ</p>
        </div>
        <div>
            <label for="gift_course_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ซื้อคอร์สนี้</label>
            <select id="gift_course_ids" name="gift_course_ids[]" multiple class="{{ $inputClass }} h-36">
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected(in_array((string) $course->id, $selectedGiftCourses, true))>{{ $course->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-4">
            <div>
                <label for="gift_assessment_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">แถมข้อสอบ</label>
                <select id="gift_assessment_ids" name="gift_assessment_ids[]" multiple class="{{ $inputClass }} h-28">
                    @foreach ($exams as $exam)
                        <option value="{{ $exam->id }}" @selected(in_array((string) $exam->id, $selectedGiftExams, true))>{{ $exam->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="gift_product_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">แถมหนังสือ</label>
                <select id="gift_product_ids" name="gift_product_ids[]" multiple class="{{ $inputClass }} h-28">
                    @foreach ($books as $book)
                        <option value="{{ $book->id }}" @selected(in_array((string) $book->id, $selectedGiftBooks, true))>{{ $book->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="md:col-span-2">
            <label for="required_video_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ต้องดูวิดีโอก่อนใช้โปรโมชัน</label>
            <select id="required_video_ids" name="required_video_ids[]" multiple class="{{ $inputClass }} h-36">
                @foreach ($videos as $video)
                    <option value="{{ $video->id }}" @selected(in_array((string) $video->id, $selectedRequiredVideos, true))>{{ $video->title }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ไม่เลือก เท่ากับใช้โปรโมชันได้โดยไม่ต้องดูวิดีโอ</p>
        </div>
    </div>
</div>

<div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">สินค้าที่ใช้ส่วนลดได้</h4>
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
