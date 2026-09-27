{{-- Global submit / navigation loading overlay (driven by Alpine.store('loading')) --}}
<div
    x-data
    x-cloak
    x-show="$store.loading.active"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-999999 flex items-center justify-center bg-gray-900/40"
    role="status"
    aria-live="polite"
    aria-busy="true"
>
    <div class="mx-4 flex min-w-[220px] flex-col items-center gap-3 rounded-2xl border border-gray-200 bg-white px-6 py-5 shadow-theme-lg dark:border-gray-700 dark:bg-gray-900">
        <div class="border-brand-500 h-10 w-10 animate-spin rounded-full border-4 border-solid border-t-transparent"></div>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-200" x-text="$store.loading.message"></p>
    </div>
</div>
