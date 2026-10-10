<?php

use App\Enums\PaymentStatus;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Mail\TemplatedMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withHeaders(['X-Api-Key' => 'test-storefront-key']);

    foreach (['admin', 'staff', 'student'] as $name) {
        Role::query()->create([
            'name' => $name,
            'display_name' => ucfirst($name),
        ]);
    }
});

function makeStudent(array $overrides = []): User
{
    $user = User::query()->create(array_merge([
        'name' => 'Student',
        'email' => 'student@example.com',
        'password' => 'password123',
        'status' => 'active',
        'email_verified_at' => now(),
    ], $overrides));

    $user->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());

    return $user;
}

it('rejects requests without the storefront key', function () {
    $this->withHeaders(['X-Api-Key' => 'wrong-key'])
        ->getJson('/api/v1/courses')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'ไม่ได้รับอนุญาต');
});

it('registers a student and emails a confirmation link', function () {
    Mail::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'ใหม่ ใจดี',
        'email' => 'new@example.com',
        'phone' => '0812345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated()
        ->assertJsonPath('email', 'new@example.com')
        ->assertJsonMissingPath('token');

    $user = User::query()->where('email', 'new@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('student'))->toBeTrue()
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('password123', $user->password))->toBeTrue();

    Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
        return $mail->hasTo('new@example.com')
            && $mail->mailSubject === 'ยืนยันอีเมล '.config('app.name');
    });

    $this->postJson('/api/v1/auth/login', [
        'email' => 'new@example.com',
        'password' => 'password123',
    ])->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email_verified', false);

    config(['services.storefront.app_url' => 'http://student.test']);
    $url = URL::temporarySignedRoute('api.auth.email.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->withoutHeader('X-Api-Key')->get($url)
        ->assertRedirect('http://student.test/verify-email?status=success');

    expect($user->fresh()->email_verified_at)->not->toBeNull();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'new@example.com',
        'password' => 'password123',
    ])->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email_verified', true);
});

it('rejects an invalid confirmation link', function () {
    config(['services.storefront.app_url' => 'http://student.test']);

    $this->get('/api/v1/auth/email/verify/1/not-a-real-hash')
        ->assertRedirect('http://student.test/verify-email?status=invalid');
});

it('resends a confirmation link only while the email is unverified', function () {
    Mail::fake();
    $student = makeStudent([
        'email' => 'pending@example.com',
        'email_verified_at' => null,
    ]);

    $this->postJson('/api/v1/auth/email/resend', [
        'email' => $student->email,
    ])->assertOk();

    Mail::assertSent(TemplatedMail::class);
    Mail::fake();

    $student->forceFill(['email_verified_at' => now()])->save();

    $this->postJson('/api/v1/auth/email/resend', [
        'email' => $student->email,
    ])->assertOk();

    Mail::assertNothingSent();
});

it('rejects admin login on the student api', function () {
    $admin = User::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'status' => 'active',
    ]);
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'password123',
    ])->assertForbidden();
});

it('lists published courses, curriculums, and exams in one catalog', function () {
    Course::query()->create([
        'name' => 'คอร์สเปิดขาย',
        'slug' => 'open-course',
        'price' => 1500,
        'sale_price' => 990,
        'status' => 'published',
    ]);
    Course::query()->create([
        'name' => 'คอร์สฉบับร่าง',
        'slug' => 'draft-course',
        'price' => 500,
        'status' => 'draft',
    ]);
    Curriculum::query()->create([
        'name' => 'แพ็กเกจฟิสิกส์',
        'slug' => 'physics-pack',
        'price' => 2500,
        'status' => 'published',
    ]);
    Assessment::query()->create([
        'title' => 'ข้อสอบขายแยก',
        'slug' => 'sale-exam',
        'type' => 'primary_exam',
        'status' => 'published',
        'is_independent' => true,
        'price' => 490,
        'duration_minutes' => 60,
    ]);
    Assessment::query()->create([
        'title' => 'แบบฝึกหัดในคอร์ส',
        'slug' => 'course-quiz',
        'type' => 'quiz',
        'status' => 'published',
        'price' => 0,
    ]);
    Product::query()->create([
        'name' => 'หนังสือคณิต',
        'slug' => 'math-book',
        'type' => 'book',
        'price' => 290,
        'stock' => 4,
        'status' => 'active',
    ]);

    $this->getJson('/api/v1/catalog')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonFragment([
            'item_type' => 'course',
            'slug' => 'open-course',
            'effective_price' => 990,
        ])
        ->assertJsonFragment([
            'item_type' => 'curriculum',
            'slug' => 'physics-pack',
        ])
        ->assertJsonFragment([
            'item_type' => 'exam',
            'slug' => 'sale-exam',
            'duration_minutes' => 60,
        ])
        ->assertJsonMissing(['slug' => 'draft-course'])
        ->assertJsonMissing(['slug' => 'course-quiz']);

    $this->getJson('/api/v1/catalog?type=exam')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.item_type', 'exam');

    $this->getJson('/api/v1/catalog?type=book')->assertStatus(422);

    $this->getJson('/api/v1/exams/sale-exam')
        ->assertOk()
        ->assertJsonPath('data.item_type', 'exam')
        ->assertJsonPath('data.name', 'ข้อสอบขายแยก')
        ->assertJsonMissingPath('data.questions');

    $this->getJson('/api/v1/books/math-book')
        ->assertOk()
        ->assertJsonPath('data.name', 'หนังสือคณิต')
        ->assertJsonPath('data.in_stock', true);

    $this->getJson('/api/v1/courses/draft-course')->assertNotFound();
});

it('creates a course order and accepts a payment slip', function () {
    Storage::fake(MediaStorage::disk());
    $student = makeStudent();
    $token = $student->createToken('student')->plainTextToken;

    $course = Course::query()->create([
        'name' => 'คอร์สฟิสิกส์',
        'slug' => 'physics',
        'price' => 1200,
        'status' => 'published',
    ]);

    $order = $this->withToken($token)->postJson('/api/v1/orders', [
        'items' => [
            ['type' => 'course', 'id' => $course->id],
        ],
    ])        ->assertCreated()
        ->assertJsonPath('data.kind', 'enrollment')
        ->assertJsonPath('data.total', 1200)
        ->assertJsonPath('data.payment_status', PaymentStatus::Pending->value)
        ->assertJsonPath('data.requires_shipping', false);

    $this->withToken($token)->post('/api/v1/orders/'.$order->json('data.id').'/payment', [
        'slip' => UploadedFile::fake()->image('slip.jpg'),
        'note' => 'โอนแล้ว',
    ])->assertOk()
        ->assertJsonPath('data.payment_status', PaymentStatus::AwaitingVerification->value)
        ->assertJsonPath('data.payment_status_label', 'รอตรวจสอบ');
});

it('requires a shipping address when the cart has a book', function () {
    $student = makeStudent();
    $token = $student->createToken('student')->plainTextToken;
    $book = Product::query()->create([
        'name' => 'หนังสือเคมี',
        'slug' => 'chem-book',
        'type' => 'book',
        'price' => 350,
        'stock' => 2,
        'status' => 'active',
    ]);

    $this->withToken($token)->postJson('/api/v1/orders', [
        'items' => [
            ['type' => 'book', 'id' => $book->id, 'quantity' => 1],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors(['shipping.name', 'shipping.address_line1']);

    $this->withToken($token)->postJson('/api/v1/orders', [
        'items' => [
            ['type' => 'book', 'id' => $book->id, 'quantity' => 1],
        ],
        'shipping' => [
            'name' => 'ใหม่ ใจดี',
            'phone' => '0812345678',
            'address_line1' => '99/1',
            'district' => 'เมือง',
            'province' => 'เชียงใหม่',
            'postal_code' => '50000',
        ],
    ])->assertCreated()
        ->assertJsonPath('data.kind', 'book')
        ->assertJsonPath('data.requires_shipping', true)
        ->assertJsonPath('data.shipping.province', 'เชียงใหม่');

    $this->withToken($token)->postJson('/api/v1/orders', [
        'items' => [
            ['type' => 'book', 'id' => $book->id, 'quantity' => 1],
            ['type' => 'course', 'id' => 1],
        ],
        'shipping' => [
            'name' => 'ใหม่ ใจดี',
            'phone' => '0812345678',
            'address_line1' => '99/1',
            'district' => 'เมือง',
            'province' => 'เชียงใหม่',
            'postal_code' => '50000',
        ],
    ])->assertStatus(422)->assertJsonValidationErrors(['items']);
});

it('requires a token to read the profile', function () {
    $this->getJson('/api/v1/profile')->assertUnauthorized();

    $student = makeStudent();
    $token = $student->createToken('student')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.email', 'student@example.com');

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
    $this->withToken($token)->getJson('/api/v1/profile')->assertUnauthorized();
});
