<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\OrderKind;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ออเดอร์สมัครเรียน (orders.kind = enrollment) แยกหน้าจากออเดอร์หนังสือ
 * แก้ไข บันทึก ลบ และการชำระเงินยังใช้ OrderController / OrderPaymentController ร่วมกัน
 */
class EnrollmentOrderController extends Controller
{
    private const ITEM_TYPES = [
        'course' => 'วิชา',
        'curriculum' => 'หลักสูตร',
        'exam' => 'ข้อสอบ',
    ];

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $paymentStatus = $request->string('payment_status')->toString();
        $type = $request->string('type')->toString();
        $type = array_key_exists($type, self::ITEM_TYPES) ? $type : '';
        $column = ['course' => 'course_id', 'curriculum' => 'curriculum_id', 'exam' => 'assessment_id'][$type] ?? null;

        $orders = Order::query()
            ->with(['user:id,name,email,phone', 'latestPayment', 'items'])
            ->where('kind', OrderKind::Enrollment->value)
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function (Builder $inner) use ($like) {
                    $inner->where('order_no', 'like', $like)
                        ->orWhereHas('items', fn ($items) => $items->where('item_name', 'like', $like))
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like));
                });
            })
            ->when($column, fn ($query) => $query->whereHas('items', fn ($items) => $items->whereNotNull($column)))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($paymentStatus !== '', fn ($query) => $query->where('payment_status', $paymentStatus))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.enrollments.index', [
            'title' => 'สมัครเรียน',
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'type' => $type,
            ],
            'itemTypes' => self::ITEM_TYPES,
            'orderStatuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
        ]);
    }

    /**
     * ค้นหานักเรียนสำหรับปุ่มสมัครเรียน แล้วพาไปหน้าสร้างออเดอร์ของนักเรียนคนนั้น
     */
    public function students(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        $students = User::query()
            ->students()
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $phone = Phone::looksLikePhone($search) ? Phone::normalize($search) : null;

                $query->where(function (Builder $inner) use ($like, $phone) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->when($phone, fn ($byPhone) => $byPhone->orWhere('phone', 'like', '%'.$phone.'%'));
                });
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone', 'status']);

        return response()->json([
            'data' => $students->map(fn (User $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'contact' => $student->email ?: $student->phone,
                'status' => $student->status,
                'status_label' => AccountStatus::labelFor($student->status),
                'order_url' => route('students.orders.create', $student),
            ])->values(),
        ]);
    }

    public function show(Order $order): View|RedirectResponse
    {
        if ($order->isBook()) {
            return redirect()->route('orders.show', $order);
        }

        $order->load([
            'user',
            'items.course:id,name,code',
            'items.curriculum:id,name',
            'items.assessment:id,title',
            'payments',
            'latestPayment',
            'coupon',
            'receipt',
            'enrollments.course:id,name',
            'enrollments.curriculum:id,name',
            'enrollments.assessment:id,title',
        ]);

        return view('pages.enrollments.show', [
            'title' => trim(($order->user?->name ?? '').' '.$order->order_no),
            'order' => $order,
            'itemTypes' => self::ITEM_TYPES,
        ]);
    }
}
