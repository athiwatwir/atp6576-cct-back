<div x-show="tab === 'chapters'" x-cloak>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">จัดการบทเรียน</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เลือกบททางซ้าย แล้วจัดการวิดีโอ/เอกสารทางขวา</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        {{-- Chapters list --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 shadow-theme-xs dark:border-gray-800 lg:col-span-4">
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/80 px-4 py-3 dark:border-gray-800 dark:bg-white/[0.02]">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">บทเรียน</h4>
                <button type="button" @click="showChapterForm = !showChapterForm; editingChapter = false" class="{{ $btn['primarySm'] }}">
                    + เพิ่มบท
                </button>
            </div>

            <div x-show="showChapterForm" x-cloak class="border-b border-gray-100 bg-brand-50/40 p-4 dark:border-gray-800 dark:bg-brand-500/5">
                <form method="POST" action="{{ route('courses.chapters.store', $course) }}" class="space-y-3">
                    @csrf
                    <input type="text" name="title" required placeholder="ชื่อบท เช่น บทที่ 1 จำนวนเต็ม" class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <textarea name="description" rows="2" placeholder="รายละเอียดสั้นๆ" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                    <div class="flex gap-2">
                        <button type="submit" class="{{ $btn['primarySm'] }}">บันทึก</button>
                        <button type="button" @click="showChapterForm = false" class="{{ $btn['secondarySm'] }}">ยกเลิก</button>
                    </div>
                </form>
            </div>

            <div class="max-h-[520px] space-y-1 overflow-y-auto p-2" x-init="initChapterSortable($el)">
                @forelse ($course->chapters as $chapter)
                <div data-chapter-id="{{ $chapter->id }}" class="flex items-stretch gap-1 rounded-xl transition" :class="selectedChapter === {{ $chapter->id }}
                            ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-200 dark:bg-brand-500/15 dark:text-brand-400 dark:ring-brand-500/30'
                            : 'hover:bg-gray-50 dark:hover:bg-white/5'">
                    <button type="button" class="chapter-drag-handle flex cursor-grab items-center px-2 text-gray-400 active:cursor-grabbing dark:text-gray-500" title="ลากเพื่อเรียงลำดับ" aria-label="ลากเพื่อเรียงลำดับ">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm8-12a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                        </svg>
                    </button>
                    <button type="button" @click="if (uploadingVideo) return; selectedChapter = {{ $chapter->id }}; closeVideoModal(); showDocumentForm = false; editingVideoId = null" class="min-w-0 flex-1 px-2 py-3 text-left">
                        <div class="flex items-center gap-2">
                            <span class="chapter-seq inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-white/80 px-1.5 text-[11px] font-semibold opacity-70 dark:bg-white/10">{{ $chapter->seq }}</span>
                            <div class="truncate text-sm font-medium">{{ $chapter->title }}</div>
                        </div>
                        <div class="mt-1 text-xs opacity-70">
                            วิดีโอ {{ $chapter->videos->count() }} · เอกสาร {{ $chapter->documents->count() }}
                        </div>
                    </button>
                </div>
                @empty
                <p class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีบทเรียน</p>
                @endforelse
            </div>
        </div>

        {{-- Chapter content --}}
        <div class="lg:col-span-8">
            @forelse ($course->chapters as $chapter)
            <div x-show="selectedChapter === {{ $chapter->id }}" x-cloak class="space-y-5">
                <div class="overflow-hidden rounded-2xl border border-gray-200 shadow-theme-xs dark:border-gray-800">
                    <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-white/[0.02]">
                        <div class="min-w-0">
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $chapter->title }}</h4>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $chapter->description ?: 'ไม่มีรายละเอียด' }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="editingChapter = !editingChapter; closeVideoModal(); showDocumentForm = false; editingVideoId = null"
                                :class="editingChapter ? @js($btn['editActive']) : @js($btn['edit'])"
                            >แก้ไขบท</button>
                            <button
                                type="button"
                                @click="openVideoModal()"
                                :class="showVideoForm ? @js($btn['primarySm']) : @js($btn['view'])"
                            >+ เพิ่มวิดีโอ</button>
                            <button
                                type="button"
                                @click="showDocumentForm = !showDocumentForm; closeVideoModal(); editingChapter = false; editingVideoId = null"
                                :class="showDocumentForm ? @js($btn['successActive']) : @js($btn['success'])"
                            >+ เพิ่มเอกสาร</button>
                            <form method="POST" action="{{ route('courses.chapters.destroy', [$course, $chapter]) }}" onsubmit="return confirm('ลบบทเรียนนี้และเนื้อหาที่เกี่ยวข้องหรือไม่?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="{{ $btn['danger'] }}">ลบ</button>
                            </form>
                        </div>
                    </div>

                    <div x-show="editingChapter" x-cloak class="border-b border-gray-100 bg-warning-50/40 p-4 dark:border-gray-800 dark:bg-warning-500/5">
                        <form method="POST" action="{{ route('courses.chapters.update', [$course, $chapter]) }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="title" value="{{ $chapter->title }}" required class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                            <textarea name="description" rows="2" class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2">{{ $chapter->description }}</textarea>
                            <div class="flex gap-2 md:col-span-2">
                                <button type="submit" class="{{ $btn['primarySm'] }}">บันทึก</button>
                                <button type="button" @click="editingChapter = false" class="{{ $btn['secondarySm'] }}">ยกเลิก</button>
                            </div>
                        </form>
                    </div>

                    {{-- Add Video Modal --}}
                    <div
                        x-show="showVideoForm && selectedChapter === {{ $chapter->id }}"
                        x-cloak
                        @keydown.escape.window="if (showVideoForm && selectedChapter === {{ $chapter->id }}) closeVideoModal()"
                        class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" data-modal
                    >
                        <div class="fixed inset-0 h-full w-full bg-gray-900/40"></div>
                        <div @click.stop class="relative w-full max-w-[700px] rounded-3xl bg-white p-5 shadow-theme-lg dark:bg-gray-900 sm:p-8">
                            <button type="button" @click="closeVideoModal()" :disabled="uploadingVideo" class="{{ $btn['icon'] }} absolute right-3 top-3 z-10 sm:right-6 sm:top-6">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.043 16.541a1 1 0 0 0 1.414 1.415L12 13.413l4.543 4.543a1 1 0 0 0 1.414-1.415L13.414 12l4.543-4.543a1 1 0 0 0-1.414-1.414L12 10.586 7.457 6.043A1 1 0 0 0 6.043 7.457L10.586 12l-4.543 4.541Z" fill="currentColor"/>
                                </svg>
                            </button>

                            <div class="pr-10">
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">เพิ่มวิดีโอ</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $chapter->title }} · อัปโหลดขึ้น Cloudflare R2</p>
                            </div>

                            <form data-no-loading @submit="uploadVideo($event)" action="{{ route('courses.chapters.videos.store', [$course, $chapter]) }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                                @csrf
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">ชื่อวิดีโอ</label>
                                    <input type="text" name="title" required placeholder="ชื่อวิดีโอ / บทเรียนย่อย" :disabled="uploadingVideo"
                                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm shadow-theme-xs disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">รายละเอียด</label>
                                    <textarea name="description" rows="2" placeholder="รายละเอียด" :disabled="uploadingVideo"
                                        class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm shadow-theme-xs disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">ไฟล์วิดีโอ</label>
                                    <input type="file" name="video_file" required accept="video/mp4,video/webm,video/quicktime" :disabled="uploadingVideo"
                                        class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm shadow-theme-xs disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                    <p class="mt-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                        รองรับ mp4, webm, mov · สูงสุด 500MB ·
                                        <code class="rounded bg-gray-100 px-1 dark:bg-white/10">courses/{{ $course->id }}/chapters/{{ $chapter->id }}/videos/{video_id}.ext</code>
                                    </p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">ความยาว (วินาที)</label>
                                    <input type="number" name="duration_seconds" min="0" placeholder="เช่น 600" :disabled="uploadingVideo"
                                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm shadow-theme-xs disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="is_free" value="1" class="rounded border-gray-300" :disabled="uploadingVideo" />
                                    เปิดให้เรียนฟรี
                                </label>

                                <div x-show="uploadingVideo || uploadStage === 'done'" x-cloak class="space-y-2 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-white/[0.03]">
                                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                                        <span x-text="uploadStage === 'uploading'
                                            ? 'กำลังอัปโหลดไฟล์...'
                                            : (uploadStage === 'saving'
                                                ? 'กำลังบันทึกไปยัง R2...'
                                                : 'อัปโหลดสำเร็จ')"></span>
                                        <span x-text="uploadProgress + '%'"></span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="bg-brand-500 h-full rounded-full transition-all duration-200" :style="'width: ' + uploadProgress + '%'"></div>
                                    </div>
                                </div>

                                <template x-if="uploadVideoError">
                                    <p class="text-sm text-error-600" x-text="uploadVideoError"></p>
                                </template>

                                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                                    <button type="button" @click="closeVideoModal()" :disabled="uploadingVideo" class="{{ $btn['secondary'] }}">ยกเลิก</button>
                                    <button type="submit" :disabled="uploadingVideo" class="{{ $btn['primary'] }}">
                                        <span x-show="!uploadingVideo">บันทึกวิดีโอ</span>
                                        <span x-show="uploadingVideo" x-cloak x-text="uploadStage === 'saving' ? 'กำลังบันทึก...' : 'กำลังอัปโหลด...'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div x-show="showDocumentForm" x-cloak class="border-b border-gray-100 bg-success-50/40 p-4 dark:border-gray-800 dark:bg-success-500/5">
                        <form method="POST" action="{{ route('courses.chapters.documents.store', [$course, $chapter]) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @csrf
                            <input type="text" name="title" required placeholder="ชื่อเอกสาร" class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                            <textarea name="description" rows="2" placeholder="รายละเอียด" class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2"></textarea>
                            <input type="file" name="file" required class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 md:col-span-2">
                                <input type="checkbox" name="is_free" value="1" class="rounded border-gray-300" />
                                เปิดให้ดาวน์โหลดฟรี
                            </label>
                            <div class="flex gap-2 md:col-span-2">
                                <button type="submit" class="{{ $btn['successActive'] }}">บันทึกเอกสาร</button>
                                <button type="button" @click="showDocumentForm = false" class="{{ $btn['secondarySm'] }}">ยกเลิก</button>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/60 dark:border-gray-800 dark:bg-white/[0.02]">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">ชื่อเนื้อหา</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">ประเภท</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @php $row = 1; @endphp
                                @foreach ($chapter->videos as $video)
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $row++ }}</td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $video->title }}</div>
                                        <div class="line-clamp-1 text-xs text-gray-500">{{ $video->description }}</div>
                                        @if ($video->is_free)
                                        <span class="bg-brand-50 text-brand-600 mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px]">เรียนฟรี</span>
                                        @endif
                                        @if ($video->storage_key)
                                        <div class="mt-1 text-[11px] text-gray-400">
                                            R2: <code class="rounded bg-gray-100 px-1 dark:bg-white/10">{{ $video->storage_key }}</code>
                                        </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">Video</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            @if ($video->url)
                                            <button
                                                type="button"
                                                @click="openVideoPlayer(@js($video->url), @js($video->title))"
                                                class="{{ $btn['view'] }}"
                                            >ดูวิดีโอ</button>
                                            @endif
                                            <button type="button" @click="editingVideoId = editingVideoId === {{ $video->id }} ? null : {{ $video->id }}" :class="editingVideoId === {{ $video->id }} ? @js($btn['editActive']) : @js($btn['edit'])">แก้ไข</button>
                                            <form method="POST" action="{{ route('courses.chapters.videos.destroy', [$course, $chapter, $video]) }}" onsubmit="return confirm('ลบวิดีโอนี้หรือไม่?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="{{ $btn['danger'] }}">ลบ</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr x-show="editingVideoId === {{ $video->id }}" x-cloak>
                                    <td colspan="4" class="bg-warning-50/50 px-4 py-4 dark:bg-warning-500/5">
                                        <form method="POST" action="{{ route('courses.chapters.videos.update', [$course, $chapter, $video]) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="title" value="{{ $video->title }}" required class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                                            <textarea name="description" rows="2" class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2">{{ $video->description }}</textarea>
                                            <div class="md:col-span-2">
                                                <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">เปลี่ยนไฟล์วิดีโอ (ถ้าต้องการ)</label>
                                                <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                                <p class="mt-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                                    อัปโหลดใหม่จะทับชื่อไฟล์
                                                    <code class="rounded bg-gray-100 px-1 dark:bg-white/10">courses/{{ $course->id }}/chapters/{{ $chapter->id }}/videos/{{ $video->id }}.ext</code>
                                                </p>
                                                @if ($video->url)
                                                <p class="mt-1 text-[11px] text-gray-500">ไฟล์ปัจจุบัน: <a href="{{ $video->url }}" target="_blank" class="text-brand-500 hover:underline">เปิดดู</a>
                                                    @if ($video->storage_key)
                                                        · <code class="rounded bg-gray-100 px-1 dark:bg-white/10">{{ $video->storage_key }}</code>
                                                    @endif
                                                </p>
                                                @endif
                                            </div>
                                            <input type="number" name="duration_seconds" min="0" value="{{ $video->duration_seconds }}" class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                                            <label class="inline-flex items-center gap-2 text-sm md:col-span-2">
                                                <input type="checkbox" name="is_free" value="1" @checked($video->is_free) class="rounded border-gray-300" />
                                                เปิดให้เรียนฟรี
                                            </label>
                                            <div class="flex gap-2 md:col-span-2">
                                                <button type="submit" class="{{ $btn['primarySm'] }}">บันทึก</button>
                                                <button type="button" @click="editingVideoId = null" class="{{ $btn['secondarySm'] }}">ยกเลิก</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach

                                @foreach ($chapter->documents as $document)
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $row++ }}</td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $document->title }}</div>
                                        <div class="text-xs text-gray-500">{{ $document->file_name }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-400">Document</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="{{ $btn['view'] }}">เปิดไฟล์</a>
                                            <form method="POST" action="{{ route('courses.chapters.documents.destroy', [$course, $chapter, $document]) }}" onsubmit="return confirm('ลบเอกสารนี้หรือไม่?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="{{ $btn['danger'] }}">ลบ</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach

                                @if ($chapter->videos->isEmpty() && $chapter->documents->isEmpty())
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        ยังไม่มีเนื้อหาในบทนี้ เริ่มเพิ่มวิดีโอหรือเอกสารได้เลย
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @empty
            <div class="rounded-2xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีบทเรียน กด “+ เพิ่มบท” เพื่อเริ่มต้น</p>
            </div>
            @endforelse

            @if ($course->chapters->isNotEmpty())
            <div x-show="!selectedChapter" class="rounded-2xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">เลือกบทเรียนทางซ้ายเพื่อจัดการเนื้อหา</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Video Player Modal --}}
    <div
        x-show="playingVideo"
        x-cloak
        @keydown.escape.window="if (playingVideo) closeVideoPlayer()"
        class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" data-modal
    >
        <div class="fixed inset-0 h-full w-full bg-gray-900/60"></div>
        <div @click.stop class="relative w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-theme-lg dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
                <div class="min-w-0">
                    <h4 class="truncate text-base font-semibold text-gray-800 dark:text-white/90" x-text="playingVideoTitle"></h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">เล่นตัวอย่างวิดีโอบทเรียน</p>
                </div>
                <button type="button" @click="closeVideoPlayer()" class="{{ $btn['icon'] }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.043 16.541a1 1 0 0 0 1.414 1.415L12 13.413l4.543 4.543a1 1 0 0 0 1.414-1.415L13.414 12l4.543-4.543a1 1 0 0 0-1.414-1.414L12 10.586 7.457 6.043A1 1 0 0 0 6.043 7.457L10.586 12l-4.543 4.541Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
            <div class="bg-black">
                <video
                    x-ref="videoPlayer"
                    :src="playingVideoUrl"
                    controls
                    playsinline
                    preload="metadata"
                    class="aspect-video max-h-[75vh] w-full bg-black"
                ></video>
            </div>
        </div>
    </div>
</div>
