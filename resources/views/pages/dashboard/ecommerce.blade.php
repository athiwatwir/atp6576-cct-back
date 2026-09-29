@extends('layouts.app')

@section('content')
<div
    x-data="{
        loading: true,
        error: '',
        data: null,
        chart: null,
        async init() {
            try {
                const response = await fetch(@js(route('dashboard.summary')), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('load failed');
                this.data = await response.json();
                this.loading = false;
                this.$nextTick(() => this.$nextTick(() => this.renderChart()));
            } catch (error) {
                this.loading = false;
                this.error = 'โหลดข้อมูลแดชบอร์ดไม่สำเร็จ';
            }
        },
        renderChart() {
            if (!this.data || !this.$refs.revenueChart || !window.ApexCharts) return;
            if (this.chart) this.chart.destroy();
            const dark = document.documentElement.classList.contains('dark');
            this.chart = new window.ApexCharts(this.$refs.revenueChart, {
                chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'รายได้', data: this.data.chart.amounts }],
                colors: ['#465fff'],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: this.data.chart.labels,
                    labels: { style: { colors: dark ? '#98a2b3' : '#667085' }, rotate: 0 },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: dark ? '#98a2b3' : '#667085' },
                        formatter: (value) => '฿' + Number(value).toLocaleString('th-TH'),
                    },
                },
                grid: { borderColor: dark ? '#1d2939' : '#f2f4f7' },
                tooltip: { y: { formatter: (value) => '฿' + Number(value).toLocaleString('th-TH', { minimumFractionDigits: 2 }) } },
            });
            this.chart.render();
        },
        statusClass(status) {
            if (status === 'paid') return 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400';
            if (status === 'awaiting_verification') return 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400';
            if (status === 'failed') return 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400';
            return 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300';
        },
    }"
    class="space-y-6"
>
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">แดชบอร์ด</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="data ? 'ข้อมูลจริงช่วง ' + data.period : 'กำลังเตรียมข้อมูลจากระบบ'"></p>
        </div>
    </div>

    <template x-if="error">
        <div class="rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600" x-text="error"></div>
    </template>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <template x-for="n in (loading ? 4 : 0)" :key="'skeleton-' + n">
            <div class="h-32 animate-pulse rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"></div>
        </template>
        <template x-for="metric in (data?.metrics || [])" :key="metric.label">
                <a :href="metric.href" class="rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand-200 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/30">
                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="metric.label"></div>
                    <div class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90" x-text="metric.value"></div>
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-400" x-text="metric.hint"></span>
                        <span x-show="metric.change !== null" class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="metric.change >= 0 ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400' : 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400'" x-text="(metric.change > 0 ? '+' : '') + metric.change + '%'"></span>
                    </div>
                </a>
        </template>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-8">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายได้ 30 วันล่าสุด</h3>
                    <p class="mt-1 text-xs text-gray-400">เฉพาะออเดอร์ที่ชำระแล้ว</p>
                </div>
            </div>
            <div x-show="loading" class="h-[280px] animate-pulse rounded-xl bg-gray-50 dark:bg-white/[0.03]"></div>
            <div x-show="data" x-ref="revenueChart"></div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายได้เดือนนี้ตามประเภท</h3>
            <div x-show="loading" class="mt-4 space-y-3">
                <template x-for="n in 4" :key="n">
                    <div class="h-10 animate-pulse rounded-lg bg-gray-50 dark:bg-white/[0.03]"></div>
                </template>
            </div>
            <div x-show="data && data.breakdown.length === 0" class="mt-8 text-center text-sm text-gray-500">ยังไม่มียอดชำระในเดือนนี้</div>
            <div class="mt-4 space-y-4" x-show="data && data.breakdown.length">
                <template x-for="row in data?.breakdown || []" :key="row.label">
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-800 dark:text-white/90" x-text="row.label"></span>
                            <span class="text-gray-500" x-text="row.amount"></span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-brand-500" :style="'width: ' + row.share + '%'"></div>
                        </div>
                        <div class="mt-1 text-[11px] text-gray-400" x-text="row.lines + ' รายการ · ' + row.share + '%'"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ออเดอร์ล่าสุด</h3>
            <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">ดูทั้งหมด</a>
        </div>
        <div x-show="loading" class="h-40 animate-pulse bg-gray-50 dark:bg-white/[0.02]"></div>
        <div class="overflow-x-auto" x-show="data">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ออเดอร์</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ลูกค้า</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">สถานะชำระ</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">เวลา</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">ยอด</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-if="data && data.orders.length === 0">
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">ยังไม่มีออเดอร์</td></tr>
                    </template>
                    <template x-for="order in data?.orders || []" :key="order.no">
                        <tr>
                            <td class="px-5 py-3"><a :href="order.url" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400" x-text="order.no"></a></td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300" x-text="order.customer"></td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium" :class="statusClass(order.status)" x-text="order.status_label"></span></td>
                            <td class="px-5 py-3 text-sm text-gray-500" x-text="order.at"></td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90" x-text="order.amount"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
