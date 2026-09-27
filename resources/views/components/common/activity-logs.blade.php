@props([
    'entity' => null,
    'logs' => null,
    'limit' => 10,
    'title' => 'ประวัติการแก้ไข',
    'empty' => 'ยังไม่มีบันทึกกิจกรรม',
    'showLink' => true,
])

@php
    use App\Models\AuditLog;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Str;

    $formatAuditValue = static function (mixed $value): string {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return Str::limit((string) $value, 80);
        }

        return Str::limit((string) json_encode($value, JSON_UNESCAPED_UNICODE), 80);
    };

    if ($logs === null) {
        $logs = collect();

        if ($entity instanceof Model) {
            $logs = AuditLog::query()
                ->with('user:id,name,email')
                ->forEntity($entity)
                ->latest('id')
                ->limit((int) $limit)
                ->get();
        }
    } elseif ($logs instanceof Collection) {
        $logs = $logs->take((int) $limit);
    } else {
        $logs = collect($logs)->take((int) $limit);
    }

    $entityFilter = $entity instanceof Model
        ? Str::of(class_basename($entity->getMorphClass()))->snake()->append('.')->toString()
        : null;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <div>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">ล่าสุด {{ $logs->count() }} รายการ</p>
        </div>
        @if ($showLink)
            <a href="{{ route('audit-logs.index', array_filter(['action' => $entityFilter])) }}"
                class="shrink-0 text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                ดูทั้งหมด
            </a>
        @endif
    </div>

    @if ($logs->isEmpty())
        <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $empty }}
        </div>
    @else
        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($logs as $log)
                <li class="px-5 py-3.5" x-data="{ open: false }">
                    <div class="flex items-start gap-3">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500"></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="text-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $log->user?->name ?? 'ระบบ / ไม่ระบุ' }}
                                </span>
                                <code class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                    {{ $log->action }}
                                </code>
                            </div>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $log->description ?: 'มีการเปลี่ยนแปลงข้อมูล' }}
                            </p>

                            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-400">
                                <span>{{ $log->created_at?->format('d/m/Y H:i') }}</span>
                                @if ($log->ip_address)
                                    <span class="font-mono">{{ $log->ip_address }}</span>
                                @endif
                                @if (! empty($log->changed_attributes))
                                    <button type="button" data-no-loading @click="open = !open"
                                        class="font-medium text-brand-600 hover:underline dark:text-brand-400"
                                        x-text="open ? 'ซ่อนรายละเอียด' : 'ดูการเปลี่ยนแปลง'">
                                    </button>
                                @endif
                                @if ($showLink)
                                    <a href="{{ route('audit-logs.show', $log) }}" class="font-medium text-gray-500 hover:underline dark:text-gray-400">
                                        รายละเอียด
                                    </a>
                                @endif
                            </div>

                            @if (! empty($log->changed_attributes))
                                <div x-show="open" x-cloak class="mt-3 overflow-hidden rounded-xl border border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                                    <div class="max-h-48 overflow-y-auto p-3">
                                        <table class="w-full text-left text-[11px]">
                                            <thead>
                                                <tr class="text-gray-400">
                                                    <th class="pb-1.5 pr-2 font-medium">ฟิลด์</th>
                                                    <th class="pb-1.5 pr-2 font-medium">เดิม</th>
                                                    <th class="pb-1.5 font-medium">ใหม่</th>
                                                </tr>
                                            </thead>
                                            <tbody class="align-top text-gray-600 dark:text-gray-300">
                                                @foreach ($log->changed_attributes as $field)
                                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                                        <td class="py-1.5 pr-2 font-mono text-gray-500">{{ $field }}</td>
                                                        <td class="py-1.5 pr-2 break-all">{{ $formatAuditValue($log->old_values[$field] ?? null) }}</td>
                                                        <td class="py-1.5 break-all">{{ $formatAuditValue($log->new_values[$field] ?? null) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
