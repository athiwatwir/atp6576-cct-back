<div x-show="tab === 'reviews'" x-cloak class="space-y-5">
    <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รีวิว</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">รีวิวจากผู้เรียนของคอร์สนี้</p>
    </div>

    <div class="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-700">
        <div class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($stats['reviews']) }}</div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            ยังไม่ได้เปิดหน้าจัดการรีวิวแบบละเอียดในรอบนี้<br>
            จำนวนรีวิวที่มีในระบบจะแสดงตรงนี้
        </p>
    </div>
</div>
