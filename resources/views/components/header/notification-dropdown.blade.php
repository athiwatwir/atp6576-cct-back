{{-- Notification Dropdown Component --}}
@php
    if (! isset($notifications) || ! isset($unreadCount)) {
        $user = auth()->user();
        $notifications = $user
            ? $user->appNotifications()->latest('id')->limit(20)->get()
            : collect();
        $unreadCount = $user
            ? (int) $user->appNotifications()->unread()->count()
            : 0;
    }
@endphp
<div class="relative" x-data="{
    dropdownOpen: false,
    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
    },
    closeDropdown() {
        this.dropdownOpen = false;
    }
}" @click.away="closeDropdown()">
    <button
        class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        @click="toggleDropdown()"
        type="button"
        aria-label="การแจ้งเตือน"
    >
        @if ($unreadCount > 0)
            <span class="absolute right-0 top-0.5 z-1 h-2 w-2 rounded-full bg-orange-400">
                <span class="absolute inline-flex w-full h-full bg-orange-400 rounded-full opacity-75 -z-1 animate-ping"></span>
            </span>
        @endif

        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z" fill="" />
        </svg>
    </button>

    <div
        x-show="dropdownOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0"
        style="display: none;"
    >
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-800">
            <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                การแจ้งเตือน
                @if ($unreadCount > 0)
                    <span class="ml-1 text-sm font-medium text-orange-500">({{ $unreadCount }})</span>
                @endif
            </h5>

            <button @click="closeDropdown()" class="text-gray-500 dark:text-gray-400" type="button" aria-label="ปิด">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z" fill="" />
                </svg>
            </button>
        </div>

        <ul class="flex flex-col h-auto overflow-y-auto custom-scrollbar">
            @forelse ($notifications as $notification)
                @php
                    $typeLabel = match ($notification->type) {
                        'student.registered' => 'สมัครสมาชิก',
                        'order.created' => 'ออเดอร์',
                        default => 'ระบบ',
                    };
                    $iconBg = $notification->type === 'order.created'
                        ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400'
                        : 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400';
                @endphp
                <li>
                    <a
                        href="{{ route('notifications.open', $notification) }}"
                        class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5 {{ $notification->isUnread() ? 'bg-orange-50/60 dark:bg-white/[0.03]' : '' }}"
                    >
                        <span class="relative mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $iconBg }}">
                            @if ($notification->type === 'order.created')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A1 1 0 0 0 9 20h10v-2H9.42a.25.25 0 0 1-.23-.15L10 16h7.45a1 1 0 0 0 .92-.62l3.24-7.29A1 1 0 0 0 20.7 6H6.21l-.94-2H1V2h4.27L7 4Zm0 16a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Zm10 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" fill="currentColor"/>
                                </svg>
                            @else
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"/>
                                </svg>
                            @endif
                            @if ($notification->isUnread())
                                <span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-orange-400 dark:border-gray-900"></span>
                            @endif
                        </span>

                        <span class="block min-w-0">
                            <span class="mb-1 block text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $notification->title }}
                            </span>
                            @if ($notification->message)
                                <span class="mb-1.5 block text-theme-sm text-gray-500 dark:text-gray-400">
                                    {{ $notification->message }}
                                </span>
                            @endif
                            <span class="flex items-center gap-2 text-gray-500 text-theme-xs dark:text-gray-400">
                                <span>{{ $typeLabel }}</span>
                                <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                                <span>{{ $notification->created_at?->locale('th')->diffForHumans() }}</span>
                            </span>
                        </span>
                    </a>
                </li>
            @empty
                <li class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    ยังไม่มีการแจ้งเตือน
                </li>
            @endforelse
        </ul>

        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" class="mt-3">
                @csrf
                <button
                    type="submit"
                    class="flex w-full justify-center rounded-lg border border-gray-300 bg-white p-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
                >
                    อ่านทั้งหมดแล้ว
                </button>
            </form>
        @endif
    </div>
</div>
