@php
    /** @var \App\Models\Course|\App\Models\Curriculum|\App\Models\Assessment|null $owner */
    $planRows = array_map(
        fn (array $row) => [...$row, 'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOL)],
        array_values((array) old('plans', \App\Support\PricePlans::formRows($owner ?? null))),
    );
    $planInput = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<div class="md:col-span-2 rounded-xl border border-gray-200 p-4 dark:border-gray-800"
    x-data="{
        plans: @js(array_values($planRows)),
        add() {
            this.plans.push({ id: null, name: '', duration_days: '', price: '0', sale_price: '', is_active: true });
        },
        remove(index) {
            if (this.plans.length > 1) this.plans.splice(index, 1);
        },
        move(index, step) {
            const target = index + step;
            if (target < 0 || target >= this.plans.length) return;
            const [row] = this.plans.splice(index, 1);
            this.plans.splice(target, 0, row);
        },
        label(plan) {
            if (plan.name) return plan.name;
            return plan.duration_days ? plan.duration_days + ' วัน' : 'ไม่จำกัดเวลา';
        },
    }">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">แพ็กเกจราคา<span class="text-error-500">*</span></h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ตั้งได้หลายราคาตามระยะเวลาเรียน เช่น 30 วัน 3,000 บาท, 60 วัน 5,000 บาท เว้นจำนวนวันว่าง = เรียนได้ไม่จำกัดเวลา ราคาที่แสดงในรายการคือแพ็กเกจที่ถูกที่สุด</p>
        </div>
        <button type="button" @click="add()" class="shrink-0 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">+ เพิ่มแพ็กเกจ</button>
    </div>

    @error('plans') <p class="mt-2 text-sm text-error-500">{{ $message }}</p> @enderror
    @foreach ($errors->get('plans.*') as $messages)
        @foreach ($messages as $message)
            <p class="mt-2 text-sm text-error-500">{{ $message }}</p>
        @endforeach
    @endforeach

    <div class="mt-4 space-y-3">
        <template x-for="(plan, index) in plans" :key="index">
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700" :class="!plan.is_active && 'opacity-60'">
                <input type="hidden" :name="`plans[${index}][id]`" :value="plan.id ?? ''" />
                <div class="grid grid-cols-2 gap-3 md:grid-cols-12">
                    <div class="col-span-2 md:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">ชื่อแพ็กเกจ</label>
                        <input type="text" maxlength="100" x-model="plan.name" :name="`plans[${index}][name]`" :placeholder="label(plan)" class="{{ $planInput }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">จำนวนวัน</label>
                        <input type="number" min="1" max="{{ \App\Support\PricePlans::MAX_DAYS }}" x-model="plan.duration_days" :name="`plans[${index}][duration_days]`" placeholder="ไม่จำกัด" class="{{ $planInput }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">ราคา (บาท)</label>
                        <input type="number" min="0" step="0.01" required x-model="plan.price" :name="`plans[${index}][price]`" class="{{ $planInput }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">ราคาลด (บาท)</label>
                        <input type="number" min="0" step="0.01" x-model="plan.sale_price" :name="`plans[${index}][sale_price]`" placeholder="-" class="{{ $planInput }}" />
                    </div>
                    <div class="col-span-2 flex items-end justify-between gap-2 md:col-span-3">
                        <label class="inline-flex h-11 items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="hidden" :name="`plans[${index}][is_active]`" :value="plan.is_active ? 1 : 0" />
                            <input type="checkbox" x-model="plan.is_active" class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
                            เปิดขาย
                        </label>
                        <div class="flex h-11 items-center gap-1">
                            <button type="button" @click="move(index, -1)" :disabled="index === 0" class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-600 disabled:opacity-40 dark:border-gray-700 dark:text-gray-300" title="เลื่อนขึ้น">↑</button>
                            <button type="button" @click="move(index, 1)" :disabled="index === plans.length - 1" class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-600 disabled:opacity-40 dark:border-gray-700 dark:text-gray-300" title="เลื่อนลง">↓</button>
                            <button type="button" @click="remove(index)" x-show="plans.length > 1" class="rounded-md border border-error-300 px-2 py-1 text-xs text-error-600 dark:border-error-500/40 dark:text-error-400">ลบ</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
