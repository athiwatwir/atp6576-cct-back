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
use App\Support\Audit;
use App\Support\Phone;
use App\Support\SimplePassword;
use App\Support\Payments\OnlinePayment;
use App\Support\StudentApp;
use App\Support\StudentPurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentPurchase $purchases,
        private readonly OnlinePayment $payments,
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
                $phone = Phone::looksLikePhone($search) ? Phone::normalize($search) : null;

                $query->where(function ($inner) use ($search, $phone) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('school_name', 'like', "%{$search}%")
                        ->when($phone, fn ($byPhone) => $byPhone->orWhere('phone', 'like', "%{$phone}%"));
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
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            ...$this->profilePlace($data),
            'password' => Str::password(32),
            'status' => $data['status'],
            'email_verified_at' => filled($data['email'] ?? null) ? now() : null,
        ]);

        if (filled($data['password'] ?? null)) {
            $student->setVisiblePassword($data['password'])->save();
        }

        $student->roles()->syncWithoutDetaching([$this->studentRole()->id]);

        return redirect()
            ->route('students.orders.create', $student)
            ->with('success', filled($data['password'] ?? null)
                ? 'สร้างนักเรียนพร้อมรหัสผ่านแล้ว เลือกวิชา หลักสูตร หรือข้อสอบ แล้วไปขั้นตอนชำระเงิน'
                : 'สร้างนักเรียนแล้ว เลือกวิชา หลักสูตร หรือข้อสอบ แล้วไปขั้นตอนชำระเงิน');
    }

    public function show(User $student): View
    {
        $this->ensureStudent($student);

        $student->load([
            'enrollments' => fn ($query) => $query
                ->with(['course:id,name', 'curriculum:id,name', 'assessment:id,title', 'order:id,order_no'])
                ->latest(),
        ]);

        return view('pages.students.show', [
            'title' => $student->name,
            'student' => $student,
            'enrollmentOrders' => $student->orders()->where('kind', 'enrollment')->latest()->limit(8)->get(),
            'bookOrders' => $student->orders()->where('kind', 'book')->latest()->limit(8)->get(),
            'stats' => [
                'active_enrollments' => $student->enrollments->where('status', EnrollmentStatus::Active->value)->count(),
                'orders' => $student->orders()->count(),
                'paid_total' => (float) $student->orders()->where('payment_status', PaymentStatus::Paid->value)->sum('total_amount'),
            ],
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
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            ...$this->profilePlace($data),
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $student->setVisiblePassword($data['password']);
        }

        if (filled($payload['email']) && $student->email_verified_at === null) {
            $payload['email_verified_at'] = now();
        }

        $student->fill($payload)->save();
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

        $method = PaymentMethod::from($request->validated('payment_method'));

        // QR / บัตร / e-Wallet → สร้างลิงก์แล้วเปิดหน้า /pay/{token} ให้ชำระจริง
        if (! $method->isManual()) {
            try {
                $link = $this->payments->linkFor($order, $request->user()?->id);
            } catch (ValidationException $exception) {
                return back()->with('error', collect($exception->errors())->flatten()->first());
            }

            return redirect()->route('payment.page', [
                'token' => $link->token,
                'method' => $method->value,
            ]);
        }

        $result = $this->purchases->pay($student, $order, $method->value);

        if ($result['already_paid']) {
            return redirect()
                ->route('students.show', $student)
                ->with('success', 'ออเดอร์นี้ชำระเงินแล้ว');
        }

        if (! $student->email) {
            return redirect()
                ->route('students.show', $student)
                ->with('success', $result['first_purchase']
                    ? 'ชำระเงินแล้ว นักเรียนไม่มีอีเมล กด "ดูรหัสผ่าน" ในกล่องข้อมูลเข้าสู่ระบบแล้วแจ้งเบอร์โทรและรหัสผ่านให้นักเรียน'
                    : 'ชำระเงินแล้ว นักเรียนไม่มีอีเมล จึงไม่ได้ส่งอีเมลผลการซื้อ');
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function profilePlace(array $data): array
    {
        return [
            'school_name' => $data['school_name'] ?? null,
            'school_province' => $data['school_province'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'subdistrict' => $data['subdistrict'] ?? null,
            'district' => $data['district'] ?? null,
            'province' => $data['province'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
        ];
    }

    /**
     * เปิดดูรหัสผ่านที่ระบบออกให้ ทุกครั้งที่เปิดดูบันทึกลง Activity Log
     */
    public function loginPassword(User $student): JsonResponse
    {
        $this->ensureStudent($student);

        $password = $student->issuedPassword();

        if ($password === null) {
            return response()->json([
                'message' => $student->credentials_pending_at
                    ? 'บัญชีนี้จะได้รหัสผ่านทางอีเมลหลังชำระเงินครั้งแรก'
                    : 'ยังไม่มีสำเนารหัสผ่านของบัญชีนี้ (ตั้งรหัสก่อนเปิดระบบนี้) จะดูได้หลังนักเรียนเข้าสู่ระบบสำเร็จครั้งถัดไป หรือกดสร้างรหัสผ่านใหม่',
                'can_reset' => $student->credentials_pending_at === null,
            ], 404);
        }

        Audit::event('student.password_viewed', 'เปิดดูรหัสผ่านเข้าสู่ระบบของ '.$student->name, entity: $student);

        return response()->json($this->loginPayload($student, $password));
    }

    /**
     * ออกรหัสผ่านใหม่ (รหัสเดิมใช้ไม่ได้ทันที) แสดงให้แอดมินแจ้งนักเรียนเอง ไม่ส่งอีเมล
     */
    public function resetLoginPassword(User $student): JsonResponse
    {
        $this->ensureStudent($student);

        if ($student->credentials_pending_at) {
            return response()->json(['message' => 'บัญชีนี้จะได้รหัสผ่านทางอีเมลหลังชำระเงินครั้งแรก'], 422);
        }

        $password = SimplePassword::generate();
        $student->setVisiblePassword($password)->save();
        $student->tokens()->delete();

        Audit::event('student.password_reset', 'สร้างรหัสผ่านเข้าสู่ระบบใหม่ให้ '.$student->name, entity: $student);

        return response()->json([
            ...$this->loginPayload($student, $password),
            'message' => 'สร้างรหัสผ่านใหม่แล้ว รหัสเดิมใช้ไม่ได้ และนักเรียนถูกออกจากระบบทุกอุปกรณ์',
        ]);
    }

    /**
     * @return array{email: string|null, phone: string|null, password: string, issued_at: string|null, login_url: string}
     */
    private function loginPayload(User $student, string $password): array
    {
        return [
            'email' => $student->email,
            'phone' => $student->phone,
            'password' => $password,
            'issued_at' => $student->issued_password_at?->timezone('Asia/Bangkok')->format('d/m/Y H:i'),
            'login_url' => StudentApp::loginUrl(),
        ];
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
