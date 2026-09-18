<div x-show="tab === 'chapters'" x-cloak>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">จัดการบทเรียน</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เลือกบททางซ้าย แล้วจัดการวิดีโอ/เอกสารทางขวา</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        {{-- Chapters list --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 lg:col-span-4">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">บทเรียน (Chapters)</h4>
                <button type="button" @click="showChapterForm = !showChapterForm; editingChapter = false"
                    class="bg-brand-500 hover:bg-brand-600 rounded-lg px-3 py-1.5 text-xs font-medium text-white">
                    + เพิ่มบท
                </button>
            </div>

            <div x-show="showChapterForm" x-cloak class="border-b border-gray-100 p-4 dark:border-gray-800">
                <form method="POST" action="{{ route('courses.chapters.store', $course) }}" class="space-y-3">
                    @csrf
                    <input type="text" name="title" required placeholder="ชื่อบท เช่น บทที่ 1 จำนวนเต็ม"
                        class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <textarea name="description" rows="2" placeholder="รายละเอียดสั้นๆ"
                        class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                    <select name="status" class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="active">เปิดใช้งาน</option>
                        <option value="inactive">ปิดใช้งาน</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-3 py-2 text-xs font-medium text-white">บันทึก</button>
                        <button type="button" @click="showChapterForm = false" class="rounded-lg border border-gray-300 px-3 py-2 text-xs dark:border-gray-700">ยกเลิก</button>
                    </div>
                </form>
            </div>

            <div class="max-h-[520px] space-y-1 overflow-y-auto p-2">
                @forelse ($course->chapters as $chapter)
                    <button type="button"
                        @click="selectedChapter = {{ $chapter->id }}; showVideoForm = false; showDocumentForm = false; editingVideoId = null"
                        class="w-full rounded-lg px-3 py-3 text-left transition"
                        :class="selectedChapter === {{ $chapter->id }}
                            ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400'
                            : 'hover:bg-gray-50 dark:hover:bg-white/5'">
                        <div class="text-sm font-medium">{{ $chapter->title }}</div>
                        <div class="mt-1 text-xs opacity-70">
                            วิดีโอ {{ $chapter->videos->count() }} · เอกสาร {{ $chapter->documents->count() }}
                        </div>
                    </button>
                @empty
                    <p class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีบทเรียน</p>
                @endforelse
            </div>
        </div>

        {{-- Chapter content --}}
        <div class="lg:col-span-8">
            @forelse ($course->chapters as $chapter)
                <div x-show="selectedChapter === {{ $chapter->id }}" x-cloak class="space-y-5">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800">
                        <div class="flex flex-col gap-3 border-b border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $chapter->title }}</h4>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $chapter->description ?: 'ไม่มีรายละเอียด' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="editingChapter = !editingChapter"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium dark:border-gray-700">แก้ไขบท</button>
                                <button type="button" @click="showVideoForm = !showVideoForm; showDocumentForm = false; editingVideoId = null"
                                    class="bg-brand-500 hover:bg-brand-600 rounded-lg px-3 py-1.5 text-xs font-medium text-white">+ เพิ่มวิดีโอ</button>
                                <button type="button" @click="showDocumentForm = !showDocumentForm; showVideoForm = false; editingVideoId = null"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium dark:border-gray-700">+ เพิ่มเอกสาร</button>
                                <form method="POST" action="{{ route('courses.chapters.destroy', [$course, $chapter]) }}"
                                    onsubmit="return confirm('ลบบทเรียนนี้และเนื้อหาที่เกี่ยวข้องหรือไม่?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-error-300 px-3 py-1.5 text-xs font-medium text-error-600">ลบ</button>
                                </form>
                            </div>
                        </div>

                        <div x-show="editingChapter" x-cloak class="border-b border-gray-100 p-4 dark:border-gray-800">
                            <form method="POST" action="{{ route('courses.chapters.update', [$course, $chapter]) }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="title" value="{{ $chapter->title }}" required
                                    class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                                <textarea name="description" rows="2"
                                    class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2">{{ $chapter->description }}</textarea>
                                <select name="status" class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="active" @selected($chapter->status === 'active')>เปิดใช้งาน</option>
                                    <option value="inactive" @selected($chapter->status === 'inactive')>ปิดใช้งาน</option>
                                </select>
                                <input type="number" name="sort_order" min="0" value="{{ $chapter->sort_order }}"
                                    class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                <div class="flex gap-2 md:col-span-2">
                                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-3 py-2 text-xs font-medium text-white">บันทึก</button>
                                    <button type="button" @click="editingChapter = false" class="rounded-lg border border-gray-300 px-3 py-2 text-xs dark:border-gray-700">ยกเลิก</button>
                                </div>
                            </form>
                        </div>

                        <div x-show="showVideoForm" x-cloak class="border-b border-gray-100 p-4 dark:border-gray-800">
                            <form method="POST" action="{{ route('courses.chapters.videos.store', [$course, $chapter]) }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                @csrf
                                <input type="text" name="title" required placeholder="ชื่อวิดีโอ / บทเรียนย่อย"
                                    class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                                <textarea name="description" rows="2" placeholder="รายละเอียด"
                                    class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2"></textarea>
                                <input type="number" name="duration_seconds" min="0" placeholder="ความยาว (วินาที)"
                                    class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                <select name="status" class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="draft">ร่าง</option>
                                    <option value="published">เปิดใช้งาน</option>
                                    <option value="inactive">ปิดใช้งาน</option>
                                </select>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 md:col-span-2">
                                    <input type="checkbox" name="is_free" value="1" class="rounded border-gray-300" />
                                    เปิดให้เรียนฟรี
                                </label>
                                <div class="flex gap-2 md:col-span-2">
                                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-3 py-2 text-xs font-medium text-white">บันทึกวิดีโอ</button>
                                    <button type="button" @click="showVideoForm = false" class="rounded-lg border border-gray-300 px-3 py-2 text-xs dark:border-gray-700">ยกเลิก</button>
                                </div>
                            </form>
                        </div>

                        <div x-show="showDocumentForm" x-cloak class="border-b border-gray-100 p-4 dark:border-gray-800">
                            <form method="POST" action="{{ route('courses.chapters.documents.store', [$course, $chapter]) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                @csrf
                                <input type="text" name="title" required placeholder="ชื่อเอกสาร"
                                    class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                                <textarea name="description" rows="2" placeholder="รายละเอียด"
                                    class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2"></textarea>
                                <input type="file" name="file" required
                                    class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                                <select name="status" class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="active">เปิดใช้งาน</option>
                                    <option value="inactive">ปิดใช้งาน</option>
                                </select>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="is_free" value="1" class="rounded border-gray-300" />
                                    เปิดให้ดาวน์โหลดฟรี
                                </label>
                                <div class="flex gap-2 md:col-span-2">
                                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-3 py-2 text-xs font-medium text-white">บันทึกเอกสาร</button>
                                    <button type="button" @click="showDocumentForm = false" class="rounded-lg border border-gray-300 px-3 py-2 text-xs dark:border-gray-700">ยกเลิก</button>
                                </div>
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">ชื่อเนื้อหา</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">ประเภท</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">สถานะ</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @php $row = 1; @endphp
                                    @foreach ($chapter->videos as $video)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $row++ }}</td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $video->title }}</div>
                                                <div class="line-clamp-1 text-xs text-gray-500">{{ $video->description }}</div>
                                                @if ($video->is_free)
                                                    <span class="bg-brand-50 text-brand-600 mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px]">เรียนฟรี</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">Video</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses[$video->status] ?? $statusClasses['draft'] }}">
                                                    {{ $statusLabels[$video->status] ?? $video->status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @click="editingVideoId = editingVideoId === {{ $video->id }} ? null : {{ $video->id }}"
                                                        class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs dark:border-gray-700">แก้ไข</button>
                                                    <form method="POST" action="{{ route('courses.chapters.videos.destroy', [$course, $chapter, $video]) }}"
                                                        onsubmit="return confirm('ลบวิดีโอนี้หรือไม่?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded-lg border border-error-300 px-2.5 py-1.5 text-xs text-error-600">ลบ</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr x-show="editingVideoId === {{ $video->id }}" x-cloak>
                                            <td colspan="5" class="bg-gray-50 px-4 py-4 dark:bg-white/[0.02]">
                                                <form method="POST" action="{{ route('courses.chapters.videos.update', [$course, $chapter, $video]) }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="title" value="{{ $video->title }}" required
                                                        class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
                                                    <textarea name="description" rows="2"
                                                        class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2">{{ $video->description }}</textarea>
                                                    <input type="number" name="duration_seconds" min="0" value="{{ $video->duration_seconds }}"
                                                        class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                                    <select name="status" class="dark:bg-dark-900 h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                        <option value="draft" @selected($video->status === 'draft')>ร่าง</option>
                                                        <option value="published" @selected($video->status === 'published')>เปิดใช้งาน</option>
                                                        <option value="inactive" @selected($video->status === 'inactive')>ปิดใช้งาน</option>
                                                    </select>
                                                    <label class="inline-flex items-center gap-2 text-sm md:col-span-2">
                                                        <input type="checkbox" name="is_free" value="1" @checked($video->is_free) class="rounded border-gray-300" />
                                                        เปิดให้เรียนฟรี
                                                    </label>
                                                    <div class="flex gap-2 md:col-span-2">
                                                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-3 py-2 text-xs font-medium text-white">บันทึก</button>
                                                        <button type="button" @click="editingVideoId = null" class="rounded-lg border border-gray-300 px-3 py-2 text-xs dark:border-gray-700">ยกเลิก</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach

                                    @foreach ($chapter->documents as $document)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $row++ }}</td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $document->title }}</div>
                                                <div class="text-xs text-gray-500">{{ $document->file_name }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">Document</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses[$document->status] ?? $statusClasses['active'] }}">
                                                    {{ $statusLabels[$document->status] ?? $document->status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex justify-end gap-2">
                                                    <a href="{{ Storage::url($document->file_path) }}" target="_blank"
                                                        class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs dark:border-gray-700">เปิดไฟล์</a>
                                                    <form method="POST" action="{{ route('courses.chapters.documents.destroy', [$course, $chapter, $document]) }}"
                                                        onsubmit="return confirm('ลบเอกสารนี้หรือไม่?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded-lg border border-error-300 px-2.5 py-1.5 text-xs text-error-600">ลบ</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if ($chapter->videos->isEmpty() && $chapter->documents->isEmpty())
                                        <tr>
                                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
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
                <div class="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีบทเรียน กด “+ เพิ่มบท” เพื่อเริ่มต้น</p>
                </div>
            @endforelse

            @if ($course->chapters->isNotEmpty())
                <div x-show="!selectedChapter" class="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">เลือกบทเรียนทางซ้ายเพื่อจัดการเนื้อหา</p>
                </div>
            @endif
        </div>
    </div>
</div>
