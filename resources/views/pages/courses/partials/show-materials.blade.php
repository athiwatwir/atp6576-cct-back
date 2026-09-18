<div x-show="tab === 'materials'" x-cloak class="space-y-5">
    <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">สื่อการสอน</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">รวมเอกสารทั้งหมดในคอร์สนี้</p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">เอกสาร</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">บท</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">สถานะ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ไฟล์</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @php $hasDocs = false; @endphp
                @foreach ($course->chapters as $chapter)
                    @foreach ($chapter->documents as $document)
                        @php $hasDocs = true; @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $document->title }}</div>
                                <div class="text-xs text-gray-500">{{ $document->file_name }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $chapter->title }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses[$document->status] ?? $statusClasses['active'] }}">
                                    {{ $statusLabels[$document->status] ?? $document->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ Storage::url($document->file_path) }}" target="_blank"
                                    class="text-brand-500 text-sm font-medium hover:underline">เปิดไฟล์</a>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                @unless ($hasDocs)
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            ยังไม่มีสื่อการสอน เพิ่มเอกสารได้จากแท็บบทเรียน
                        </td>
                    </tr>
                @endunless
            </tbody>
        </table>
    </div>
</div>
