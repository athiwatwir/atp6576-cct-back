@extends('layouts.app')

@section('content')
<div
    x-data="{
        loading: true,
        error: '',
        data: null,
        chart: null,
        preset: 'month',
        from: '',
        to: '',
        presets: [
            { key: 'today', label: 'วันนี้' },
            { key: '7d', label: '7 วัน' },
            { key: '30d', label: '30 วัน' },
            { key: 'month', label: 'เดือนนี้' },
            { key: 'last_month', label: 'เดือนที่แล้ว' },
            { key: 'year', label: 'ปีนี้' },
        ],
        ymd(date) {
            const pad = (n) => String(n).padStart(2, '0');
            return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
        },
        today() {
            return this.ymd(new Date());
        },
        rangeOf(key) {
            const now = new Date();
            const days = (n) => { const d = new Date(now); d.setDate(d.getDate() - n); return this.ymd(d); };
            return {
                today: [this.today(), this.today()],
                '7d': [days(6), this.today()],
                '30d': [days(29), this.today()],
                month: [this.ymd(new Date(now.getFullYear(), now.getMonth(), 1)), this.today()],
                last_month: [this.ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1)), this.ymd(new Date(now.getFullYear(), now.getMonth(), 0))],
                year: [this.ymd(new Date(now.getFullYear(), 0, 1)), this.today()],
            }[key];
        },
        detectPreset() {
            const match = this.presets.find((p) => { const r = this.rangeOf(p.key); return r[0] === this.from && r[1] === this.to; });
            this.preset = match ? match.key : 'custom';
        },
        choose(key) {
            [this.from, this.to] = this.rangeOf(key);
            this.preset = key;
            this.load();
        },
        applyCustom() {
            if (!this.from || !this.to) return;
            if (this.from > this.to) [this.from, this.to] = [this.to, this.from];
            this.detectPreset();
            this.load();
        },
        async init() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('from') && params.get('to')) {
                this.from = params.get('from');
                this.to = params.get('to');
                this.detectPreset();
            } else {
                [this.from, this.to] = this.rangeOf('month');
            }
            await this.load();
        },
        async load() {
            this.loading = true;
            this.error = '';
            const query = new URLSearchParams({ from: this.from, to: this.to });
            const url = new URL(window.location.href);
            url.searchParams.set('from', this.from);
            url.searchParams.set('to', this.to);
            window.history.replaceState({}, '', url);
            try {
                const response = await fetch(@js(route('dashboard.summary')) + '?' + query, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await response.json().catch(() => null);
                if (!response.ok) throw new Error(payload?.message || 'load failed');
                this.data = payload;
                this.loading = false;
                this.$nextTick(() => this.$nextTick(() => this.renderChart()));
            } catch (error) {
                this.loading = false;
                this.error = error.message && error.message !== 'load failed' ? error.message : 'โหลดข้อมูลแดชบอร์ดไม่สำเร็จ';
            }
        },
        thaiDate(value) {
            if (!value) return '';
            const [y, m, d] = value.split('-').map(Number);
            const months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            return d + ' ' + months[m - 1] + ' ' + (y + 543);
        },
        get rangeLabel() {
            if (!this.data) return '';
            const r = this.data.range;
            return r.from === r.to ? this.thaiDate(r.from) : this.thaiDate(r.from) + ' – ' + this.thaiDate(r.to);
        },
        renderChart() {
            if (!this.data || !this.$refs.revenueChart || !window.ApexCharts) return;
            if (this.chart) this.chart.destroy();
            const dark = document.documentElement.classList.contains('dark');
            const many = this.data.chart.labels.length > 16;
            this.chart = new window.ApexCharts(this.$refs.revenueChart, {
                chart: { type: this.data.chart.labels.length === 1 ? 'bar' : 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'รายได้', data: this.data.chart.amounts }],
                colors: ['#465fff'],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: this.data.chart.labels,
                    labels: { style: { colors: dark ? '#98a2b3' : '#667085' }, rotate: 0, hideOverlappingLabels: true },
                    tickAmount: many ? 10 : undefined,
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
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">แดชบอร์ด</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="data ? 'ข้อมูลช่วง ' + rangeLabel + ' (' + data.range.days + ' วัน) เทียบกับ ' + data.range.days + ' วันก่อนหน้า' : 'กำลังเตรียมข้อมูลจากระบบ'"></p>
        </div>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="flex flex-wrap gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
                <template x-for="p in presets" :key="p.key">
                    <button type="button" @click="choose(p.key)" :disabled="loading"
                        class="rounded-md px-3 py-1.5 text-xs font-medium transition disabled:opacity-60"
                        :class="preset === p.key ? 'bg-white text-gray-800 shadow-theme-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        x-text="p.label"></button>
                </template>
            </div>
            <form @submit.prevent="applyCustom()" class="flex items-center gap-2">
                <input type="date" x-model="from" :max="to || today()" required
                    class="dark:bg-dark-900 h-9 rounded-lg border px-2 text-sm text-gray-700 dark:text-white/90"
                    :class="preset === 'custom' ? 'border-brand-300 dark:border-brand-700' : 'border-gray-300 dark:border-gray-700'" />
                <span class="text-sm text-gray-400">–</span>
                <input type="date" x-model="to" :min="from" :max="today()" required
                    class="dark:bg-dark-900 h-9 rounded-lg border px-2 text-sm text-gray-700 dark:text-white/90"
                    :class="preset === 'custom' ? 'border-brand-300 dark:border-brand-700' : 'border-gray-300 dark:border-gray-700'" />
                <button type="submit" :disabled="loading" class="bg-brand-500 hover:bg-brand-600 h-9 rounded-lg px-3 text-xs font-medium text-white disabled:opacity-60">ดูข้อมูล</button>
            </form>
        </div>
    </div>

    <template x-if="error">
        <div class="rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600" x-text="error"></div>
    </template>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <template x-for="n in (loading ? 4 : 0)" :key="'skeleton-' + n">
            <div class="h-32 animate-pulse rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"></div>
        </template>
        <template x-for="metric in (loading ? [] : data?.metrics || [])" :key="metric.label">
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
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="data?.chart.granularity === 'month' ? 'รายได้รายเดือน' : 'รายได้รายวัน'">รายได้รายวัน</h3>
                    <p class="mt-1 text-xs text-gray-400" x-text="'เฉพาะออเดอร์ที่ชำระแล้ว · ' + rangeLabel"></p>
                </div>
            </div>
            <div x-show="loading" class="h-[280px] animate-pulse rounded-xl bg-gray-50 dark:bg-white/[0.03]"></div>
            <div x-show="data && !loading" x-ref="revenueChart"></div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายได้ตามประเภท</h3>
            <p class="mt-1 text-xs text-gray-400" x-text="rangeLabel"></p>
            <div x-show="loading" class="mt-4 space-y-3">
                <template x-for="n in 4" :key="n">
                    <div class="h-10 animate-pulse rounded-lg bg-gray-50 dark:bg-white/[0.03]"></div>
                </template>
            </div>
            <div x-show="!loading && data && data.breakdown.length === 0" class="mt-8 text-center text-sm text-gray-500">ไม่มียอดชำระในช่วงนี้</div>
            <div class="mt-4 space-y-4" x-show="!loading && data && data.breakdown.length">
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
            <div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ออเดอร์ล่าสุดในช่วงนี้</h3>
                <p class="mt-1 text-xs text-gray-400" x-show="data" x-text="'ทั้งหมด ' + (data?.order_count ?? 0).toLocaleString('th-TH') + ' ออเดอร์ · แสดง 8 รายการล่าสุด'"></p>
            </div>
            <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">ดูทั้งหมด</a>
        </div>
        <div x-show="loading" class="h-40 animate-pulse bg-gray-50 dark:bg-white/[0.02]"></div>
        <div class="overflow-x-auto" x-show="data && !loading">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ออเดอร์</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ลูกค้า</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">สถานะชำระ</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">เวลา</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">ยอด</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-if="data && data.orders.length === 0">
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">ไม่มีออเดอร์ในช่วงนี้</td></tr>
                    </template>
                    <template x-for="order in data?.orders || []" :key="order.no">
                        <tr>
                            <td class="px-5 py-3"><a :href="order.url" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400" x-text="order.no"></a></td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300" x-text="order.customer"></td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium" :class="statusClass(order.status)" x-text="order.status_label"></span></td>
                            <td class="px-5 py-3 text-sm text-gray-500" x-text="order.at"></td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90" x-text="order.amount"></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="order.url" title="ดูข้อมูลออเดอร์"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-blue-light-300 bg-blue-light-50 px-2.5 py-1.5 text-xs font-medium whitespace-nowrap text-blue-light-700 hover:bg-blue-light-100 dark:border-blue-light-500/40 dark:bg-blue-light-500/10 dark:text-blue-light-400 dark:hover:bg-blue-light-500/20">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        ดูข้อมูล
                                    </a>
                                    <template x-if="order.receipt_url">
                                        <a :href="order.receipt_url" target="_blank" rel="noopener" data-no-loading title="เปิดใบเสร็จ PDF เพื่อพิมพ์"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-success-300 bg-success-50 px-2.5 py-1.5 text-xs font-medium whitespace-nowrap text-success-700 hover:bg-success-100 dark:border-success-500/40 dark:bg-success-500/10 dark:text-success-400 dark:hover:bg-success-500/20">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/></svg>
                                            ปริ้นใบเสร็จ
                                        </a>
                                    </template>
                                    <template x-if="!order.receipt_url">
                                        <span title="ยังไม่ชำระเงิน จึงยังไม่มีใบเสร็จ"
                                            class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium whitespace-nowrap text-gray-400 dark:border-gray-700 dark:text-gray-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/></svg>
                                            ปริ้นใบเสร็จ
                                        </span>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
