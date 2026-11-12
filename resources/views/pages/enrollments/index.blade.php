@extends('layouts.app')

@php
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $typeBadge = [
        'course' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
        'curriculum' => 'bg-blue-light-50 text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-400',
        'exam' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
    ];
    $itemType = fn ($item) => match (true) {
        $item->course_id !== null => 'course',
        $item->curriculum_id !== null => 'curriculum',
        $item->assessment_id !== null => 'exam',
        default => null,
    };
    $hasFilters = $filters['search'] !== '' || $filters['status'] !== '' || $filters['payment_status'] !== '' || $filters['type'] !== '';
@endphp

@section('content')
    <x-common.page-breadcrumb pageTitle="สมัครเรียน" />

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการสมัครเรียน</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">คำสั่งซื้อวิชา หลักสูตร และข้อสอบ พร้อมแพ็กเกจและช่วงสิทธิ์เรียน กดสมัครเรียนเพื่อเลือกนักเรียนแล้วสร้างออเดอร์</p>
            </div>
            <div x-data="{
                    open: false,
                    search: '',
                    students: [],
                    loading: false,
                    failed: false,
                    timer: null,
                    requestId: 0,
                    show() {
                        this.open = true;
                        this.load();
                        this.$nextTick(() => this.$refs.studentSearch.focus());
                    },
                    queue() {
                        clearTimeout(this.timer);
                        this.timer = setTimeout(() => this.load(), 300);
                    },
                    load() {
                        const id = ++this.requestId;
                        this.loading = true;
                        this.failed = false;

                        fetch(@js(route('enrollments.students')) + '?search=' + encodeURIComponent(this.search.trim()), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        })
                            .then((response) => {
                                if (!response.ok) throw new Error('search failed');
                                return response.json();
                            })
                            .then((data) => {
                                if (id === this.requestId) this.students = data.data;
                            })
                            .catch(() => {
                                if (id === this.requestId) this.failed = true;
                            })
                            .finally(() => {
                                if (id === this.requestId) this.loading = false;
                            });
                    },
                }" @keydown.escape.window="open = false">
                <button type="button" data-no-loading @click="show()"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                    สมัครเรียน
                </button>

                <div x-show="open" x-cloak class="modal fixed inset-0 z-99999 flex items-start justify-center overflow-y-auto p-5 sm:items-center" data-modal style="display: none;">
                    <div class="fixed inset-0 h-full w-full bg-gray-900/40" @click="open = false"></div>
                    <div @click.stop class="relative flex max-h-[85vh] w-full max-w-lg flex-col rounded-3xl bg-white shadow-theme-lg dark:bg-gray-900">
                        <div class="border-b border-gray-100 px-6 py-5 dark:border-gray-800">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">สมัครเรียน</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เลือกนักเรียนเพื่อสร้างออเดอร์ หรือเพิ่มนักเรียนใหม่</p>
                            <input type="text" x-ref="studentSearch" x-model="search" @input="queue()"
                                placeholder="ค้นหาชื่อ / อีเมล / เบอร์โทร" class="{{ $inputClass }} mt-4" />
                        </div>

                        <div class="flex-1 overflow-y-auto p-3">
                            <p x-show="loading && students.length === 0" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">กำลังค้นหา...</p>
                            <p x-show="failed" x-cloak class="px-3 py-6 text-center text-sm text-error-600 dark:text-error-400">ค้นหาไม่สำเร็จ กรุณาลองอีกครั้ง</p>
                            <p x-show="!loading && !failed && students.length === 0" x-cloak class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">ไม่พบนักเรียน</p>
                            <template x-for="student in students" :key="student.id">
                                <a :href="student.order_url" class="flex items-center justify-between gap-3 rounded-xl px-3 py-3 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-gray-800 dark:text-white/90" x-text="student.name || '-'"></div>
                                        <div class="truncate text-xs text-gray-400" x-text="student.contact || '-'"></div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span x-show="student.status !== 'active'" class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="student.status_label"></span>
                                        <span class="text-brand-600 dark:text-brand-400 text-xs font-medium">สร้างออเดอร์</span>
                                    </div>
                                </a>
                            </template>
                            <p x-show="students.length === 10" x-cloak class="px-3 pt-2 text-center text-xs text-gray-400">แสดง 10 คนล่าสุด พิมพ์ค้นหาเพื่อหาคนอื่น</p>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                            <button type="button" @click="open = false" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">ปิด</button>
                            <a href="{{ route('students.create') }}" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">เพิ่มนักเรียนใหม่</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <form method="GET" action="{{ route('enrollments.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                        placeholder="ค้นหาเลขออเดอร์ / นักเรียน / อีเมล / เบอร์โทร / ชื่อรายการ"
                        class="{{ $inputClass }}" />
                </div>
                <div>
                    <select name="type" class="{{ $inputClass }}" onchange="this.form.submit()">
                        <option value="">ทุกประเภท</option>
                        @foreach ($itemTypes as $value => $label)
                            <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="payment_status" class="{{ $inputClass }}" onchange="this.form.submit()">
                        <option value="">สถานะชำระเงิน</option>
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="status" class="{{ $inputClass }}" onchange="this.form.submit()">
                        <option value="">สถานะออเดอร์</option>
                        @foreach ($orderStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 flex-1 items-center justify-center rounded-lg px-4 text-sm font-medium text-white whitespace-nowrap">ค้นหา</button>
                    @if ($hasFilters)
                        <a href="{{ route('enrollments.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">ล้าง</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ออเดอร์</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">นักเรียน</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">รายการที่สมัคร</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ยอดรวม</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ชำระเงิน</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($orders as $order)
                        <tr class="align-top">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <a href="{{ route('enrollments.show', $order) }}" class="font-medium text-gray-800 hover:text-brand-600 dark:text-white/90 dark:hover:text-brand-400">{{ $order->order_no }}</a>
                                <div class="mt-1 text-xs text-gray-400">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm">
                                @if ($order->user)
                                    <a href="{{ route('students.show', $order->user) }}" class="font-medium text-gray-800 hover:text-brand-600 dark:text-white/90 dark:hover:text-brand-400">{{ $order->user->name ?: '-' }}</a>
                                    <div class="text-xs text-gray-400">{{ $order->user->email ?: $order->user->phone }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="max-w-md px-5 py-4">
                                <div class="space-y-2">
                                    @foreach ($order->items as $item)
                                        @php
                                            $type = $itemType($item);
                                            $plan = $item->metadata['plan_name'] ?? \App\Models\PricePlan::durationLabel($item->access_days);
                                            $access = \App\Support\AccessPeriod::summary($item->metadata['access'] ?? null, $item->access_days);
                                        @endphp
                                        <div>
                                            <div class="flex items-start gap-2">
                                                @if ($type)
                                                    <span class="mt-0.5 inline-flex shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $typeBadge[$type] }}">{{ $itemTypes[$type] }}</span>
                                                @endif
                                                <span class="text-sm text-gray-800 dark:text-white/90">{{ $item->item_name }}</span>
                                            </div>
                                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $plan }}{{ $access ? ' · '.$access : '' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90">
                                ฿{{ number_format((float) $order->total_amount, 2) }}
                                @if ((float) $order->discount_amount > 0)
                                    <div class="text-xs font-normal text-success-600 dark:text-success-400">ส่วนลด ฿{{ number_format((float) $order->discount_amount, 2) }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ \App\Enums\PaymentMethod::tryFrom($order->latestPayment?->payment_method ?? '')?->label() ?? '-' }}
                                </div>
                                <x-common.status-badge class="mt-1" :status="\App\Enums\PaymentStatus::tryFrom($order->payment_status) ?? $order->payment_status" />
                            </td>
                            <td class="px-5 py-4">
                                <x-common.status-badge :status="\App\Enums\OrderStatus::tryFrom($order->status) ?? $order->status" />
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('enrollments.show', $order) }}"
                                        class="inline-flex items-center rounded-lg border border-blue-light-300 bg-blue-light-50 px-3 py-2 text-xs font-medium text-blue-light-700 hover:bg-blue-light-100 dark:border-blue-light-500/40 dark:bg-blue-light-500/10 dark:text-blue-light-400">
                                        ดู
                                    </a>
                                    <a href="{{ route('enrollments.edit', $order) }}"
                                        class="inline-flex items-center rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">
                                        แก้ไข
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $hasFilters ? 'ไม่พบรายการตามตัวกรอง' : 'ยังไม่มีรายการสมัครเรียน' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
@endsection
