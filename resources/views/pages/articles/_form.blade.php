@php
    /** @var \App\Models\Content|null $article */
    $article = $article ?? null;
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $selectedCourses = array_map('strval', old('course_ids', $article?->courses?->pluck('id')->all() ?? []));
    $selectedCurriculums = array_map('strval', old('curriculum_ids', $article?->curriculums?->pluck('id')->all() ?? []));
    $selectedExams = array_map('strval', old('assessment_ids', $article?->assessments?->pluck('id')->all() ?? []));
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="title" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หัวข้อ<span class="text-error-500">*</span></label>
        <input type="text" id="title" name="title" value="{{ old('title', $article?->title) }}" required class="{{ $inputClass }}" />
        @error('title') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะ<span class="text-error-500">*</span></label>
        <select id="status" name="status" required class="{{ $inputClass }}">
            @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $article?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="excerpt" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">คำเกริ่น</label>
        <textarea id="excerpt" name="excerpt" rows="3" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('excerpt', $article?->excerpt) }}</textarea>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">แสดงในการ์ดรายการบทความบนเว็บนักเรียน</p>
        @error('excerpt') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">การตั้งค่า SEO</h4>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ใช้บนเว็บนักเรียนสำหรับ title / meta description / keywords ถ้าเว้นว่าง API จะใช้หัวข้อและคำเกริ่นแทน</p>
        <div class="mt-4 grid grid-cols-1 gap-5">
            <div>
                <label for="seo_title" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">SEO title</label>
                <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $article?->seo_title) }}" maxlength="200" class="{{ $inputClass }}" placeholder="{{ $article?->title ?: 'ใช้หัวข้อบทความถ้าเว้นว่าง' }}" />
                @error('seo_title') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="seo_description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">SEO description</label>
                <textarea id="seo_description" name="seo_description" rows="3" maxlength="500" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="ใช้คำเกริ่นถ้าเว้นว่าง">{{ old('seo_description', $article?->seo_description) }}</textarea>
                @error('seo_description') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="seo_keywords" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">SEO keywords</label>
                <input type="text" id="seo_keywords" name="seo_keywords" value="{{ old('seo_keywords', $article?->seo_keywords) }}" maxlength="500" class="{{ $inputClass }}" placeholder="คณิต, ข้อสอบ, ม.1" />
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">คั่นด้วยเครื่องหมายจุลภาค</p>
                @error('seo_keywords') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="md:col-span-2">
        <label for="content" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">เนื้อหา</label>
        <textarea id="content" name="content" rows="14" class="article-editor-source dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm leading-6 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('content', $article?->content) }}</textarea>
        <div class="article-editor" data-article-editor data-source="content" data-upload-url="{{ route('articles.images.store') }}" hidden>
            <div class="article-editor-toolbar" role="toolbar" aria-label="เครื่องมือจัดรูปแบบ">
                <button type="button" data-editor-command="heading-2" title="หัวข้อใหญ่">H2</button>
                <button type="button" data-editor-command="heading-3" title="หัวข้อรอง">H3</button>
                <span class="article-editor-divider"></span>
                <button type="button" data-editor-command="bold" title="ตัวหนา"><strong>B</strong></button>
                <button type="button" data-editor-command="italic" title="ตัวเอียง"><em>I</em></button>
                <button type="button" data-editor-command="underline" title="ขีดเส้นใต้"><span class="underline">U</span></button>
                <button type="button" data-editor-command="strike" title="ขีดฆ่า"><span class="line-through">S</span></button>
                <span class="article-editor-divider"></span>
                <button type="button" data-editor-command="bullet" title="รายการจุด">• รายการ</button>
                <button type="button" data-editor-command="ordered" title="รายการตัวเลข">1. รายการ</button>
                <button type="button" data-editor-command="quote" title="คำพูด">“ ”</button>
                <button type="button" data-editor-command="code" title="โค้ด">{ }</button>
                <span class="article-editor-divider"></span>
                <button type="button" data-editor-command="align-left" title="ชิดซ้าย">ซ้าย</button>
                <button type="button" data-editor-command="align-center" title="กึ่งกลาง">กลาง</button>
                <button type="button" data-editor-command="align-right" title="ชิดขวา">ขวา</button>
                <span class="article-editor-divider"></span>
                <button type="button" data-editor-command="link" title="แทรกลิงก์">ลิงก์</button>
                <button type="button" data-editor-command="image" title="แทรกรูป">รูป</button>
                <span class="article-editor-divider"></span>
                <button type="button" data-editor-command="undo" title="ย้อนกลับ">ย้อน</button>
                <button type="button" data-editor-command="redo" title="ทำซ้ำ">ทำซ้ำ</button>
            </div>
            <div data-editor-root></div>
            <p data-editor-status class="article-editor-status"></p>
        </div>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">จัดหัวข้อ รายการ ลิงก์ และแทรกรูปในเนื้อหาได้</p>
        @error('content') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="image" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รูปปก</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">JPG, PNG หรือ WebP ไม่เกิน 4 MB</p>
        @error('image') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
        @if ($article?->image_url)
            <img src="{{ $article->image_url }}" alt="" class="mt-3 h-32 w-48 rounded-xl object-cover" />
            <label class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image')) class="rounded border-gray-300" />
                ลบรูปปัจจุบัน
            </label>
        @endif
    </div>

    <div class="md:col-span-2 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายการที่เกี่ยวข้อง</h4>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เลือกวิชา หลักสูตร หรือข้อสอบขายแยกได้หลายรายการ เพื่อแสดงลิงก์ในหน้ารายละเอียดบทความบนเว็บนักเรียน กด Ctrl (หรือ Cmd) ค้างเพื่อเลือกหลายรายการ</p>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="course_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">วิชา</label>
                <select id="course_ids" name="course_ids[]" multiple class="{{ $inputClass }} h-40">
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(in_array((string) $course->id, $selectedCourses, true))>{{ $course->name }}</option>
                    @endforeach
                </select>
                @error('course_ids') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
                @error('course_ids.*') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="curriculum_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หลักสูตร</label>
                <select id="curriculum_ids" name="curriculum_ids[]" multiple class="{{ $inputClass }} h-40">
                    @foreach ($curriculums as $curriculum)
                        <option value="{{ $curriculum->id }}" @selected(in_array((string) $curriculum->id, $selectedCurriculums, true))>{{ $curriculum->name }}</option>
                    @endforeach
                </select>
                @error('curriculum_ids') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
                @error('curriculum_ids.*') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="assessment_ids" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ข้อสอบขายแยก</label>
                <select id="assessment_ids" name="assessment_ids[]" multiple class="{{ $inputClass }} h-40">
                    @foreach ($exams as $exam)
                        <option value="{{ $exam->id }}" @selected(in_array((string) $exam->id, $selectedExams, true))>{{ $exam->title }}</option>
                    @endforeach
                </select>
                @error('assessment_ids') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
                @error('assessment_ids.*') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>
