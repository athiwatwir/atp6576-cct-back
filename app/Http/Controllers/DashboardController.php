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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('pages.dashboard.ecommerce', [
            'title' => 'แดชบอร์ด',
        ]);
    }

    public function summary(): JsonResponse
    {
        $payload = Cache::remember('dashboard.summary', now()->addSeconds(45), function () {
            $now = now();
            $monthStart = $now->copy()->startOfMonth();
            $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $lastMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
            $chartStart = $now->copy()->subDays(29)->startOfDay();

            $revenue = $this->paidSum($monthStart, $now);
            $previousRevenue = $this->paidSum($lastMonthStart, $lastMonthEnd);
            $paidOrders = $this->paidCount($monthStart, $now);

            $students = User::students()->count();
            $newStudents = User::students()->where('created_at', '>=', $monthStart)->count();
            $activeEnrollments = Enrollment::query()->where('status', EnrollmentStatus::Active->value)->count();
            $publishedCourses = Course::query()->where('status', 'published')->count();
            $awaiting = Order::query()->where('payment_status', PaymentStatus::AwaitingVerification->value)->count();
            $awaitingAmount = (float) Order::query()
                ->where('payment_status', PaymentStatus::AwaitingVerification->value)
                ->sum('total_amount');

            $daily = Order::query()
                ->selectRaw('DATE(paid_at) as day, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as amount')
                ->where('payment_status', PaymentStatus::Paid->value)
                ->whereBetween('paid_at', [$chartStart, $now])
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

            $labels = [];
            $amounts = [];
            $orderCounts = [];
            for ($cursor = $chartStart->copy(); $cursor->lte($now); $cursor->addDay()) {
                $key = $cursor->toDateString();
                $row = $daily->get($key);
                $labels[] = $cursor->format('d/m');
                $amounts[] = round((float) ($row->amount ?? 0), 2);
                $orderCounts[] = (int) ($row->orders ?? 0);
            }

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
                ->whereBetween('orders.paid_at', [$monthStart, $now])
                ->selectRaw($itemType.' as item_type, COALESCE(SUM(order_items.total_price), 0) as amount, COUNT(*) as item_count')
                ->groupByRaw($itemType)
                ->orderByDesc('amount')
                ->get();

            $breakdownTotal = max(1, (float) $breakdown->sum('amount'));

            $orders = Order::query()
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
                        'at' => $order->created_at?->format('d/m/Y H:i'),
                        'url' => route('orders.show', $order),
                    ];
                })
                ->all();

            return [
                'period' => $monthStart->format('d/m/Y').' – '.$now->format('d/m/Y'),
                'metrics' => [
                    [
                        'label' => 'รายได้เดือนนี้',
                        'value' => $this->money($revenue),
                        'hint' => number_format($paidOrders).' ออเดอร์ที่ชำระแล้ว',
                        'change' => $this->change($revenue, $previousRevenue),
                        'href' => route('finance.index'),
                    ],
                    [
                        'label' => 'นักเรียน',
                        'value' => number_format($students),
                        'hint' => 'ใหม่เดือนนี้ '.number_format($newStudents).' คน',
                        'change' => null,
                        'href' => route('students.index'),
                    ],
                    [
                        'label' => 'กำลังเรียน',
                        'value' => number_format($activeEnrollments),
                        'hint' => 'คอร์สที่เผยแพร่ '.number_format($publishedCourses).' รายการ',
                        'change' => null,
                        'href' => route('courses.index'),
                    ],
                    [
                        'label' => 'รอตรวจสอบ',
                        'value' => number_format($awaiting),
                        'hint' => $this->money($awaitingAmount),
                        'change' => null,
                        'href' => route('finance.index'),
                    ],
                ],
                'chart' => [
                    'labels' => $labels,
                    'amounts' => $amounts,
                'orders' => $orderCounts,
            ],
            'breakdown' => $breakdown->map(fn ($row) => [
                    'label' => match ($row->item_type) {
                        'course' => 'คอร์ส',
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
            ];
        });

        return response()->json($payload);
    }

    private function paidSum(Carbon $from, Carbon $to): float
    {
        return (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('total_amount');
    }

    private function paidCount(Carbon $from, Carbon $to): int
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
