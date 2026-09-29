<?php

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
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
    ], $overrides));

    $user->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());

    return $user;
}

it('registers a student and returns a bearer token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'ใหม่ ใจดี',
        'email' => 'new@example.com',
        'phone' => '0812345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'new@example.com')
        ->assertJsonPath('token_type', 'Bearer');

    $user = User::query()->where('email', 'new@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('student'))->toBeTrue()
        ->and(Hash::check('password123', $user->password))->toBeTrue();
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

it('lists only published courses and books for sale', function () {
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
    Product::query()->create([
        'name' => 'หนังสือคณิต',
        'slug' => 'math-book',
        'type' => 'book',
        'price' => 290,
        'stock' => 4,
        'status' => 'active',
    ]);

    $this->getJson('/api/v1/courses')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'open-course')
        ->assertJsonPath('data.0.effective_price', 990);

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
    ])->assertCreated()
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
        ->assertJsonPath('data.requires_shipping', true)
        ->assertJsonPath('data.shipping.province', 'เชียงใหม่');
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
