@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="รายละเอียด Log" />

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Log #{{ $log->id }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $log->created_at?->format('d/m/Y H:i:s') }}</p>
    </div>
    <a href="{{ route('audit-logs.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
        กลับ
    </a>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
    <div class="space-y-6 xl:col-span-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ข้อมูลการกระทำ</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Action</dt>
                    <dd><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-white/10">{{ $log->action }}</code></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">คำอธิบาย</dt>
                    <dd class="text-right text-gray-800 dark:text-white/90">{{ $log->description ?: '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">ผู้ทำรายการ</dt>
                    <dd class="text-right">
                        <div class="font-medium text-gray-800 dark:text-white/90">{{ $log->user?->name ?? 'ระบบ / ไม่ระบุ' }}</div>
                        <div class="text-xs text-gray-400">{{ $log->user?->email }}</div>
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Entity</dt>
                    <dd class="text-right text-gray-800 dark:text-white/90">
                        @if ($log->entity_type)
                            {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Batch</dt>
                    <dd class="font-mono text-xs text-gray-500">{{ $log->batch_uuid ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">Request</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">IP</dt>
                    <dd class="font-mono text-xs">{{ $log->ip_address ?: '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Method</dt>
                    <dd>{{ $log->request_method ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">URL</dt>
                    <dd class="mt-1 break-all text-xs text-gray-600 dark:text-gray-400">{{ $log->request_url ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">User Agent</dt>
                    <dd class="mt-1 break-all text-xs text-gray-500">{{ $log->user_agent ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="space-y-6 xl:col-span-7">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">ค่าเดิม (old_values)</h3>
            <pre class="overflow-x-auto rounded-xl bg-gray-50 p-4 text-xs text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">ค่าใหม่ (new_values)</h3>
            <pre class="overflow-x-auto rounded-xl bg-gray-50 p-4 text-xs text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Properties</h3>
            <pre class="overflow-x-auto rounded-xl bg-gray-50 p-4 text-xs text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
        </div>
    </div>
</div>
@endsection
