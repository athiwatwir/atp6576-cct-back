<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\PricePlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * แพ็กเกจราคาของคอร์ส หลักสูตร และข้อสอบขายแยก
 * คอลัมน์ price / sale_price ของรายการหลักเก็บราคาของแพ็กเกจที่ถูกที่สุดไว้แสดงในรายการ
 */
class PricePlans
{
    public const MAX_PLANS = 10;

    public const MAX_DAYS = 3650;

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'plans' => ['required', 'array', 'min:1', 'max:'.self::MAX_PLANS],
            'plans.*.id' => ['nullable', 'integer'],
            'plans.*.name' => ['nullable', 'string', 'max:100'],
            'plans.*.duration_days' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_DAYS],
            'plans.*.price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'plans.*.sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999', 'lte:plans.*.price'],
            'plans.*.is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'plans' => 'แพ็กเกจราคา',
            'plans.*.name' => 'ชื่อแพ็กเกจ',
            'plans.*.duration_days' => 'จำนวนวัน',
            'plans.*.price' => 'ราคา',
            'plans.*.sale_price' => 'ราคาลด',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'plans.required' => 'ต้องมีแพ็กเกจราคาอย่างน้อย 1 แพ็กเกจ',
            'plans.min' => 'ต้องมีแพ็กเกจราคาอย่างน้อย 1 แพ็กเกจ',
            'plans.*.sale_price.lte' => 'ราคาลดต้องไม่สูงกว่าราคาปกติของแพ็กเกจเดียวกัน',
        ];
    }

    /**
     * @param  mixed  $plans
     * @return list<array<string, mixed>>
     */
    public static function normalizeInput(mixed $plans): array
    {
        return collect(is_array($plans) ? $plans : [])
            ->filter(fn ($plan) => is_array($plan))
            ->map(fn (array $plan) => [
                'id' => filled($plan['id'] ?? null) ? $plan['id'] : null,
                'name' => filled($plan['name'] ?? null) ? trim((string) $plan['name']) : null,
                'duration_days' => filled($plan['duration_days'] ?? null) ? $plan['duration_days'] : null,
                'price' => ($plan['price'] ?? '') === '' ? 0 : $plan['price'],
                'sale_price' => filled($plan['sale_price'] ?? null) ? $plan['sale_price'] : null,
                'is_active' => filter_var($plan['is_active'] ?? true, FILTER_VALIDATE_BOOL),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $plans
     */
    public static function assertHasActive(array $plans): void
    {
        if (! collect($plans)->contains(fn (array $plan) => (bool) ($plan['is_active'] ?? true))) {
            throw ValidationException::withMessages([
                'plans' => 'ต้องเปิดขายอย่างน้อย 1 แพ็กเกจ',
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $plans
     */
    public static function sync(Course|Curriculum|Assessment $owner, array $plans): void
    {
        self::assertHasActive($plans);
        $column = self::ownerColumn($owner);

        DB::transaction(function () use ($owner, $plans, $column) {
            $existing = PricePlan::query()->where($column, $owner->id)->get()->keyBy('id');
            $keep = [];

            foreach (array_values($plans) as $index => $data) {
                $attributes = [
                    'name' => $data['name'] ?? null,
                    'duration_days' => $data['duration_days'] !== null ? (int) $data['duration_days'] : null,
                    'price' => round((float) $data['price'], 2),
                    'sale_price' => $data['sale_price'] !== null ? round((float) $data['sale_price'], 2) : null,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'sort_order' => $index + 1,
                ];

                $plan = isset($data['id']) ? $existing->get((int) $data['id']) : null;

                if ($plan) {
                    $plan->update($attributes);
                } else {
                    $plan = PricePlan::query()->create([$column => $owner->id, ...$attributes]);
                }

                $keep[] = $plan->id;
            }

            PricePlan::query()->where($column, $owner->id)->whereNotIn('id', $keep)->delete();

            self::refreshCache($owner);
        });
    }

    /**
     * อัปเดตราคาแสดงผลของรายการหลักให้เท่ากับแพ็กเกจที่ถูกที่สุด
     */
    public static function refreshCache(Course|Curriculum|Assessment $owner): void
    {
        $cheapest = PricePlan::query()
            ->where(self::ownerColumn($owner), $owner->id)
            ->active()
            ->get()
            ->sortBy(fn (PricePlan $plan) => [$plan->effective_price, $plan->id])
            ->first();

        if (! $cheapest) {
            return;
        }

        if ($owner instanceof Assessment) {
            $owner->forceFill(['price' => $cheapest->effective_price])->saveQuietly();

            return;
        }

        $owner->forceFill([
            'price' => $cheapest->price,
            'sale_price' => $cheapest->sale_price,
        ])->saveQuietly();
    }

    /**
     * เลือกแพ็กเกจที่จะขาย ถ้าไม่ระบุและมีแพ็กเกจเปิดขายแพ็กเกจเดียวจะใช้แพ็กเกจนั้น
     * รายการเก่าที่ยังไม่มีแพ็กเกจเลยขายแบบไม่จำกัดเวลาตามราคาเดิม
     *
     * @return array{plan_id: ?int, label: string, duration_days: ?int, price: float}
     */
    public static function resolve(Course|Curriculum|Assessment $owner, mixed $planId): array
    {
        /** @var Collection<int, PricePlan> $plans */
        $plans = $owner->relationLoaded('activePricePlans')
            ? $owner->activePricePlans
            : $owner->activePricePlans()->get();

        if ($plans->isEmpty()) {
            if (filled($planId)) {
                throw ValidationException::withMessages(['items' => 'ไม่พบแพ็กเกจราคาที่เลือก']);
            }

            return [
                'plan_id' => null,
                'label' => PricePlan::durationLabel(null),
                'duration_days' => null,
                'price' => round((float) $owner->effective_price, 2),
            ];
        }

        if (blank($planId)) {
            if ($plans->count() > 1) {
                throw ValidationException::withMessages([
                    'items' => 'กรุณาเลือกแพ็กเกจราคาของ '.self::ownerName($owner),
                ]);
            }

            $plan = $plans->first();
        } else {
            $plan = $plans->firstWhere('id', (int) $planId);

            if (! $plan) {
                throw ValidationException::withMessages([
                    'items' => 'แพ็กเกจราคาที่เลือกของ '.self::ownerName($owner).' ไม่เปิดขายแล้ว',
                ]);
            }
        }

        return [
            'plan_id' => $plan->id,
            'label' => $plan->label,
            'duration_days' => $plan->duration_days,
            'price' => round($plan->effective_price, 2),
        ];
    }

    /**
     * ชื่อรายการบนออเดอร์ ใส่ระยะเวลาเมื่อเป็นแพ็กเกจแบบมีวันหมดอายุหรือมีหลายแพ็กเกจ
     *
     * @param  array{plan_id: ?int, label: string, duration_days: ?int, price: float}  $resolved
     */
    public static function itemName(string $name, array $resolved, int $activePlanCount): string
    {
        if ($resolved['duration_days'] === null && $activePlanCount <= 1) {
            return $name;
        }

        return $name.' ('.$resolved['label'].')';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function payload(Course|Curriculum|Assessment $owner): array
    {
        $plans = $owner->relationLoaded('activePricePlans')
            ? $owner->activePricePlans
            : $owner->activePricePlans()->get();

        return $plans->map(fn (PricePlan $plan) => [
            'id' => $plan->id,
            'name' => $plan->label,
            'duration_days' => $plan->duration_days,
            'price' => (float) $plan->price,
            'sale_price' => $plan->sale_price !== null ? (float) $plan->sale_price : null,
            'effective_price' => $plan->effective_price,
        ])->values()->all();
    }

    /**
     * ค่าตั้งต้นของฟอร์มแอดมิน
     *
     * @return list<array<string, mixed>>
     */
    public static function formRows(Course|Curriculum|Assessment|null $owner): array
    {
        if ($owner?->exists) {
            $rows = $owner->pricePlans()->get()->map(fn (PricePlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'duration_days' => $plan->duration_days,
                'price' => (string) $plan->price,
                'sale_price' => $plan->sale_price !== null ? (string) $plan->sale_price : null,
                'is_active' => $plan->is_active,
            ])->values()->all();

            if ($rows !== []) {
                return $rows;
            }

            return [[
                'id' => null,
                'name' => null,
                'duration_days' => null,
                'price' => (string) $owner->price,
                'sale_price' => $owner instanceof Assessment ? null : ($owner->sale_price !== null ? (string) $owner->sale_price : null),
                'is_active' => true,
            ]];
        }

        return [[
            'id' => null,
            'name' => null,
            'duration_days' => null,
            'price' => '0',
            'sale_price' => null,
            'is_active' => true,
        ]];
    }

    public static function ownerColumn(Course|Curriculum|Assessment $owner): string
    {
        return match (true) {
            $owner instanceof Course => 'course_id',
            $owner instanceof Curriculum => 'curriculum_id',
            $owner instanceof Assessment => 'assessment_id',
        };
    }

    private static function ownerName(Course|Curriculum|Assessment $owner): string
    {
        return $owner instanceof Assessment ? (string) $owner->title : (string) $owner->name;
    }
}
