@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="Activity Log" />

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Activity Log</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">บันทึกการสร้าง / แก้ไข / ลบ และการกระทำสำคัญในระบบ</p>
        </div>
    </div>

    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ $filters['search'] }}"
                    placeholder="ค้นหา action / คำอธิบาย / IP / URL"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
            </div>
            <div>
                <input type="text" name="action" value="{{ $filters['action'] }}"
                    placeholder="กรอง action เช่น order."
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex flex-1 items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">ค้นหา</button>
                <a href="{{ route('audit-logs.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">ล้าง</a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">เวลา</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ผู้ทำรายการ</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Action</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">รายละเอียด</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">IP</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($logs as $log)
                <tr>
                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                        {{ $log->created_at?->format('d/m/Y H:i:s') }}
                    </td>
                    <td class="px-5 py-4 text-sm">
                        <div class="font-medium text-gray-800 dark:text-white/90">{{ $log->user?->name ?? 'ระบบ / ไม่ระบุ' }}</div>
                        <div class="text-xs text-gray-400">{{ $log->user?->email }}</div>
                    </td>
                    <td class="px-5 py-4">
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ $log->action }}</code>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                        <div class="line-clamp-2">{{ $log->description ?: '-' }}</div>
                        @if ($log->entity_type)
                            <div class="mt-1 text-[11px] text-gray-400">{{ class_basename($log->entity_type) }} #{{ $log->entity_id }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-4 font-mono text-xs text-gray-500">{{ $log->ip_address ?: '-' }}</td>
                    <td class="px-5 py-4 text-right">
                        <a href="{{ route('audit-logs.show', $log) }}" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center rounded-lg px-3 py-2 text-xs font-medium text-white">ดู</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีบันทึก</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
    <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
