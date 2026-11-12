<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** วันที่ในตัวกรองเป็นวันตามเวลาไทย ฐานข้อมูลเก็บเวลา UTC */
    private const TIMEZONE = 'Asia/Bangkok';

    private const MAX_DAYS = 1100;

    /** ช่วงยาวกว่านี้กราฟรวมเป็นรายเดือน */
    private const DAILY_CHART_MAX_DAYS = 62;

    public function index(): View
    {
        return view('pages.dashboard.ecommerce', [
            'title' => 'แดชบอร์ด',
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $today = now(self::TIMEZONE);
        $from = $request->filled('from')
            ? Carbon::createFromFormat('Y-m-d', $request->string('from'), self::TIMEZONE)->startOfDay()
            : $today->copy()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::createFromFormat('Y-m-d', $request->string('to'), self::TIMEZONE)->endOfDay()
            : $today->copy()->endOfDay();

        if ($to->greaterThan($today->copy()->endOfDay())) {
            $to = $today->copy()->endOfDay();
        }

        abort_if($from->greaterThan($to), 422, 'วันเริ่มต้องไม่หลังวันสิ้นสุด และเลือกได้ถึงวันนี้');

        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        abort_if($days > self::MAX_DAYS, 422, 'เลือกช่วงได้ไม่เกิน 3 ปี');

        $cacheKey = 'dashboard.summary.v2.'.$from->toDateString().'.'.$to->toDateString();

        $payload = Cache::remember($cacheKey, now()->addSeconds(45), fn () => $this->build($from, $to, $days));

        return response()->json($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function build(Carbon $from, Carbon $to, int $days): array
    {
        $fromUtc = $from->copy()->utc();
        $toUtc = $to->copy()->utc();
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        $revenue = $this->paidSum($fromUtc, $toUtc);
        $previousRevenue = $this->paidSum($previousFrom->copy()->utc(), $previousTo->copy()->utc());
        $paidOrders = $this->paidCount($fromUtc, $toUtc);
        $newStudents = User::students()->whereBetween('created_at', [$fromUtc, $toUtc])->count();
        $previousNewStudents = User::students()->whereBetween('created_at', [$previousFrom->copy()->utc(), $previousTo->copy()->utc()])->count();

        $students = User::students()->count();
        $activeEnrollments = Enrollment::query()->where('status', EnrollmentStatus::Active->value)->count();
        $publishedCourses = Course::query()->where('status', 'published')->count();
        $awaiting = Order::query()->where('payment_status', PaymentStatus::AwaitingVerification->value)->count();
        $awaitingAmount = (float) Order::query()
            ->where('payment_status', PaymentStatus::AwaitingVerification->value)
            ->sum('total_amount');

        $chart = $this->chart($from, $to, $days);

        $itemType = "
            CASE
                WHEN order_items.course_id IS NOT NULL THEN 'course'
                WHEN order_items.curriculum_id IS NOT NULL THEN 'curriculum'
                WHEN order_items.assessment_id IS NOT NULL THEN 'exam'
                WHEN order_items.product_id IS NOT NULL THEN 'book'
                ELSE 'other'
            END
        ";

        $breakdown = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereBetween('orders.paid_at', [$fromUtc, $toUtc])
            ->selectRaw($itemType.' as item_type, COALESCE(SUM(order_items.total_price), 0) as amount, COUNT(*) as item_count')
            ->groupByRaw($itemType)
            ->orderByDesc('amount')
            ->get();

        $breakdownTotal = max(1, (float) $breakdown->sum('amount'));

        $ordersInRange = Order::query()->whereBetween('created_at', [$fromUtc, $toUtc]);
        $orderCount = (clone $ordersInRange)->count();

        $orders = (clone $ordersInRange)
            ->with('user:id,name')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function (Order $order) {
                $status = PaymentStatus::tryFrom((string) $order->payment_status);

                return [
                    'no' => $order->order_no,
                    'customer' => $order->user?->name ?: '-',
                    'status' => $order->payment_status,
                    'status_label' => $status?->label() ?? $order->payment_status,
                    'amount' => $this->money($order->total_amount),
                    'at' => $order->created_at?->timezone(self::TIMEZONE)->format('d/m/Y H:i'),
                    'url' => $order->adminUrl(),
                    'receipt_url' => $status === PaymentStatus::Paid
                        ? route('orders.receipt', [$order, 'inline' => 1])
                        : null,
                ];
            })
            ->all();

        return [
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $days,
                'previous_from' => $previousFrom->toDateString(),
                'previous_to' => $previousTo->toDateString(),
            ],
            'period' => $from->format('d/m/Y').' – '.$to->format('d/m/Y'),
            'metrics' => [
                [
                    'label' => 'รายได้',
                    'value' => $this->money($revenue),
                    'hint' => number_format($paidOrders).' ออเดอร์ที่ชำระแล้ว',
                    'change' => $this->change($revenue, $previousRevenue),
                    'href' => route('finance.index'),
                ],
                [
                    'label' => 'นักเรียนใหม่',
                    'value' => number_format($newStudents),
                    'hint' => 'ทั้งหมด '.number_format($students).' คน',
                    'change' => $this->change($newStudents, $previousNewStudents),
                    'href' => route('students.index'),
                ],
                [
                    'label' => 'กำลังเรียน',
                    'value' => number_format($activeEnrollments),
                    'hint' => 'ปัจจุบัน · วิชาที่เผยแพร่ '.number_format($publishedCourses).' รายการ',
                    'change' => null,
                    'href' => route('courses.index'),
                ],
                [
                    'label' => 'รอตรวจสอบ',
                    'value' => number_format($awaiting),
                    'hint' => 'ปัจจุบัน · '.$this->money($awaitingAmount),
                    'change' => null,
                    'href' => route('finance.index'),
                ],
            ],
            'chart' => $chart,
            'breakdown' => $breakdown->map(fn ($row) => [
                'label' => match ($row->item_type) {
                    'course' => 'วิชา',
                    'curriculum' => 'หลักสูตร',
                    'exam' => 'ข้อสอบ',
                    'book' => 'หนังสือ',
                    default => 'อื่นๆ',
                },
                'amount' => $this->money($row->amount),
                'lines' => (int) $row->item_count,
                'share' => (int) round(((float) $row->amount / $breakdownTotal) * 100),
            ])->values()->all(),
            'orders' => $orders,
            'order_count' => $orderCount,
        ];
    }

    /**
     * รายได้รายวัน (ช่วงไม่เกิน 62 วัน) หรือรายเดือน ตามวันที่ไทย
     *
     * @return array{granularity: string, labels: list<string>, amounts: list<float>, orders: list<int>}
     */
    private function chart(Carbon $from, Carbon $to, int $days): array
    {
        $monthly = $days > self::DAILY_CHART_MAX_DAYS;
        $offset = $from->format('P');
        $localTime = "CONVERT_TZ(paid_at, '+00:00', '{$offset}')";
        $bucket = $monthly ? "DATE_FORMAT({$localTime}, '%Y-%m')" : "DATE({$localTime})";

        $rows = Order::query()
            ->selectRaw("{$bucket} as bucket, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as amount")
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from->copy()->utc(), $to->copy()->utc()])
            ->groupBy('bucket')
            ->get()
            ->keyBy(fn ($row) => (string) $row->bucket);

        $labels = [];
        $amounts = [];
        $orders = [];
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        $cursor = $monthly ? $from->copy()->startOfMonth() : $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $monthly ? $cursor->format('Y-m') : $cursor->toDateString();
            $row = $rows->get($key);
            $labels[] = $monthly ? $months[$cursor->month].' '.substr((string) ($cursor->year + 543), -2) : $cursor->format('d/m');
            $amounts[] = round((float) ($row->amount ?? 0), 2);
            $orders[] = (int) ($row->orders ?? 0);
            $monthly ? $cursor->addMonthNoOverflow() : $cursor->addDay();
        }

        return [
            'granularity' => $monthly ? 'month' : 'day',
            'labels' => $labels,
            'amounts' => $amounts,
            'orders' => $orders,
        ];
    }

    private function paidSum(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('total_amount');
    }

    private function paidCount(CarbonInterface $from, CarbonInterface $to): int
    {
        return Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to])
            ->count();
    }

    private function change(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100.0 : 0.0;
        }

        return round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
    }

    private function money(float|int|string|null $amount): string
    {
        return '฿'.number_format((float) $amount, 2);
    }
}
