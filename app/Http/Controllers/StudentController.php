<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Students\PayStudentOrderRequest;
use App\Http\Requests\Students\StoreStudentOrderRequest;
use App\Http\Requests\Students\StoreStudentRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Support\StudentPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentPurchase $purchases,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $students = User::query()
            ->students()
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => fn ($query) => $query->where('status', EnrollmentStatus::Active->value),
                'orders',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.students.index', [
            'title' => 'นักเรียน',
            'students' => $students,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.students.create', [
            'title' => 'เพิ่มนักเรียน',
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $student = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Str::password(32),
            'status' => $data['status'],
            'email_verified_at' => now(),
        ]);

        $student->roles()->syncWithoutDetaching([$this->studentRole()->id]);

        return redirect()
            ->route('students.orders.create', $student)
            ->with('success', 'สร้างนักเรียนแล้ว เลือกคอร์ส หลักสูตร หรือข้อสอบ แล้วไปขั้นตอนชำระเงิน');
    }

    public function show(User $student): View
    {
        $this->ensureStudent($student);

        $student->load([
            'enrollments' => fn ($query) => $query
                ->with(['course:id,name', 'curriculum:id,name', 'assessment:id,title', 'order:id,order_no'])
                ->latest(),
            'orders' => fn ($query) => $query->latest()->limit(8),
        ]);

        return view('pages.students.show', [
            'title' => $student->name,
            'student' => $student,
        ]);
    }

    public function edit(User $student): View
    {
        $this->ensureStudent($student);

        return view('pages.students.edit', [
            'title' => 'แก้ไขนักเรียน',
            'student' => $student,
        ]);
    }

    public function update(UpdateStudentRequest $request, User $student): RedirectResponse
    {
        $this->ensureStudent($student);

        $data = $request->validated();
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $student->update($payload);
        $student->roles()->syncWithoutDetaching([$this->studentRole()->id]);

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'บันทึกข้อมูลนักเรียนเรียบร้อยแล้ว');
    }

    public function destroy(User $student): RedirectResponse
    {
        $this->ensureStudent($student);

        if ($student->canAccessBackend()) {
            return redirect()
                ->route('students.show', $student)
                ->with('error', 'บัญชีนี้มีบทบาทผู้ดูแลระบบ ต้องจัดการจากหน้าผู้ใช้งานระบบ');
        }

        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('success', 'ลบนักเรียนเรียบร้อยแล้ว');
    }

    public function createOrder(User $student): View
    {
        $this->ensureStudent($student);

        return view('pages.students.order', [
            'title' => 'สร้างออเดอร์',
            'student' => $student,
            'catalog' => $this->purchases->catalog(),
            'hasPaidOrder' => $this->purchases->hasPaidOrder($student),
        ]);
    }

    public function storeOrder(StoreStudentOrderRequest $request, User $student): RedirectResponse
    {
        $this->ensureStudent($student);

        $order = $this->purchases->createOrder(
            $student,
            $request->validated('items'),
            $request->validated('notes'),
            $request->validated('coupon_code'),
        );

        return redirect()
            ->route('students.orders.payment', [$student, $order])
            ->with('success', 'สร้างออเดอร์แล้ว ดำเนินการชำระเงินเพื่อเปิดสิทธิ์เรียนและส่งอีเมล');
    }

    public function payment(User $student, Order $order): View|RedirectResponse
    {
        $this->ensureOwnedOrder($student, $order);

        if ($order->payment_status === PaymentStatus::Paid->value) {
            return redirect()
                ->route('students.show', $student)
                ->with('success', 'ออเดอร์นี้ชำระเงินแล้ว');
        }

        $order->load(['items', 'coupon']);

        return view('pages.students.payment', [
            'title' => 'ชำระเงิน',
            'student' => $student,
            'order' => $order,
            'paymentMethods' => PaymentMethod::options(),
            'hasPaidOrder' => $this->purchases->hasPaidOrder($student, $order),
        ]);
    }

    public function pay(PayStudentOrderRequest $request, User $student, Order $order): RedirectResponse
    {
        $this->ensureOwnedOrder($student, $order);

        $result = $this->purchases->pay($student, $order, $request->validated('payment_method'));

        if ($result['already_paid']) {
            return redirect()
                ->route('students.show', $student)
                ->with('success', 'ออเดอร์นี้ชำระเงินแล้ว');
        }

        if (! $result['mailed']) {
            return redirect()
                ->route('students.show', $student)
                ->with('error', 'บันทึกการชำระเงินแล้ว แต่ส่งอีเมลไม่สำเร็จ');
        }

        $message = $result['first_purchase']
            ? 'ชำระเงินแล้ว ส่งอีเมลผลการซื้อพร้อมข้อมูลสำหรับเข้าระบบ'
            : 'ชำระเงินแล้ว ส่งอีเมลผลการซื้อ';

        return redirect()
            ->route('students.show', $student)
            ->with('success', $message);
    }

    private function ensureStudent(User $student): void
    {
        abort_unless($student->roles()->where('name', 'student')->exists(), 404);
    }

    private function ensureOwnedOrder(User $student, Order $order): void
    {
        $this->ensureStudent($student);
        abort_unless($order->user_id === $student->id, 404);
    }

    private function studentRole(): Role
    {
        return Role::query()->firstOrCreate(
            ['name' => 'student'],
            [
                'display_name' => 'นักเรียน',
                'description' => 'Student account — cannot access admin panel',
            ],
        );
    }
}
