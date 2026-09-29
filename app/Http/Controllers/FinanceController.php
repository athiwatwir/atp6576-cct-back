<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Support\StudentPurchase;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        $from = $this->parseDate($request->input('from'), now()->startOfMonth()->startOfDay());
        $to = $this->parseDate($request->input('to'), now()->endOfDay(), endOfDay: true);

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $preset = $request->string('preset')->toString();

        $ordersInPeriod = Order::query()
            ->whereBetween('created_at', [$from, $to]);

        $paidOrders = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to]);

        $summary = [
            'revenue' => (float) (clone $paidOrders)->sum('total_amount'),
            'paid_orders' => (clone $paidOrders)->count(),
            'order_count' => (clone $ordersInPeriod)->count(),
            'subtotal' => (float) (clone $paidOrders)->sum('subtotal'),
            'shipping' => (float) (clone $paidOrders)->sum('shipping_amount'),
            'discount' => (float) (clone $paidOrders)->sum('discount_amount'),
            'awaiting' => (float) Order::query()
                ->where('payment_status', PaymentStatus::AwaitingVerification->value)
                ->whereBetween('created_at', [$from, $to])
                ->sum('total_amount'),
            'pending' => (float) Order::query()
                ->where('payment_status', PaymentStatus::Pending->value)
                ->whereBetween('created_at', [$from, $to])
                ->sum('total_amount'),
            'refunded' => (float) Order::query()
                ->where('payment_status', PaymentStatus::Refunded->value)
                ->whereBetween('updated_at', [$from, $to])
                ->sum('total_amount'),
            'cancelled_orders' => Order::query()
                ->whereNotNull('cancelled_at')
                ->whereBetween('cancelled_at', [$from, $to])
                ->count(),
        ];

        $byPaymentStatus = Order::query()
            ->select('payment_status', DB::raw('COUNT(*) as total_orders'), DB::raw('COALESCE(SUM(total_amount), 0) as total_amount'))
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('payment_status')
            ->orderBy('payment_status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->payment_status,
                'label' => PaymentStatus::tryFrom((string) $row->payment_status)?->label() ?? $row->payment_status,
                'orders' => (int) $row->total_orders,
                'amount' => (float) $row->total_amount,
            ]);

        $byPaymentMethod = Payment::query()
            ->select('payment_method', DB::raw('COUNT(*) as total_payments'), DB::raw('COALESCE(SUM(amount), 0) as total_amount'))
            ->where('status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to])
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method,
                'label' => PaymentMethod::tryFrom((string) $row->payment_method)?->label() ?? $row->payment_method,
                'payments' => (int) $row->total_payments,
                'amount' => (float) $row->total_amount,
            ]);

        $byItemType = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereBetween('orders.paid_at', [$from, $to])
            ->selectRaw("
                CASE
                    WHEN order_items.product_id IS NOT NULL THEN 'book'
                    WHEN order_items.course_id IS NOT NULL THEN 'course'
                    WHEN order_items.assessment_id IS NOT NULL THEN 'exam'
                    WHEN order_items.curriculum_id IS NOT NULL THEN 'curriculum'
                    ELSE 'other'
                END as item_type,
                COUNT(*) as line_count,
                COALESCE(SUM(order_items.quantity), 0) as quantity,
                COALESCE(SUM(order_items.total_price), 0) as total_amount
            ")
            ->groupBy('item_type')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->item_type,
                'label' => match ($row->item_type) {
                    'book' => 'หนังสือ',
                    'course' => 'คอร์ส',
                    'exam' => 'ข้อสอบ',
                    'curriculum' => 'หลักสูตร',
                    default => 'อื่นๆ',
                },
                'lines' => (int) $row->line_count,
                'quantity' => (int) $row->quantity,
                'amount' => (float) $row->total_amount,
            ]);

        $dailyRevenue = Order::query()
            ->select(DB::raw('DATE(paid_at) as day'), DB::raw('COUNT(*) as orders'), DB::raw('COALESCE(SUM(total_amount), 0) as amount'))
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to])
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => Carbon::parse($row->day)->format('d/m/Y'),
                'orders' => (int) $row->orders,
                'amount' => (float) $row->amount,
            ]);

        $payments = Payment::query()
            ->with(['order.user:id,name,email', 'order.items:id,order_id,item_name'])
            ->whereBetween('created_at', [$from, $to])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pages.finance.index', [
            'title' => 'การเงิน',
            'from' => $from,
            'to' => $to,
            'preset' => $preset,
            'summary' => $summary,
            'byPaymentStatus' => $byPaymentStatus,
            'byPaymentMethod' => $byPaymentMethod,
            'byItemType' => $byItemType,
            'dailyRevenue' => $dailyRevenue,
            'payments' => $payments,
            'paymentStatuses' => PaymentStatus::options(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function updatePayment(Request $request, Payment $payment, StudentPurchase $purchases): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_column(PaymentStatus::cases(), 'value'))],
        ]);

        $purchases->review($payment, PaymentStatus::from($data['status']));

        return redirect()
            ->route('finance.index', array_filter([
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'preset' => $request->input('preset'),
            ], fn ($value) => filled($value)))
            ->with('success', 'บันทึกสถานะการชำระเงินแล้ว');
    }

    private function parseDate(mixed $value, Carbon $fallback, bool $endOfDay = false): Carbon
    {
        if (blank($value)) {
            return $fallback->copy();
        }

        try {
            $date = Carbon::parse((string) $value);

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }
}
