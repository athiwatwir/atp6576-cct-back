@props(['cancel' => 'closeVideoModal()'])

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
    <p class="mt-1.5 text-[11px] text-gray-500 dark:text-gray-400">รองรับ mp4, webm, mov · สูงสุด 500MB</p>
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
    <button type="button" @click="{{ $cancel }}" :disabled="uploadingVideo"
        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
        ยกเลิก
    </button>
    <button type="submit" :disabled="uploadingVideo || {{ $attributes->get('submit-disabled', 'false') }}"
        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60">
        <span x-show="!uploadingVideo">บันทึกวิดีโอ</span>
        <span x-show="uploadingVideo" x-cloak x-text="uploadStage === 'saving' ? 'กำลังบันทึก...' : 'กำลังอัปโหลด...'"></span>
    </button>
</div>
