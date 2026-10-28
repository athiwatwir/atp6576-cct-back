<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\LearningController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\GuestCheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\VideoStreamController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::get('auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->whereNumber('id')
        ->name('api.auth.email.verify');
});

// แท็ก <video> ส่ง X-Api-Key ไม่ได้ จึงใช้ลิงก์ลงลายเซ็นแทน ตัวเล่นขอทีละช่วงหลายครั้งต่อคลิป
Route::get('v1/stream/videos/{video}', [VideoStreamController::class, 'show'])
    ->middleware(['signed', 'throttle:900,1,video-stream'])
    ->whereNumber('video')
    ->name('api.videos.stream');

Route::prefix('v1/payments')->middleware('throttle:120,1')->group(function () {
    Route::post('kgp/notify', [PaymentWebhookController::class, 'kgp'])->name('api.payments.kgp.notify');
    Route::post('kgp/refund-notify', [PaymentWebhookController::class, 'kgpRefund'])->name('api.payments.kgp.refund-notify');
    Route::post('omise/webhook', [PaymentWebhookController::class, 'omise'])->name('api.payments.omise.webhook');
});

Route::prefix('v1')->middleware(['storefront', 'throttle:60,1'])->group(function () {
    // ตัวจำกัดระดับ route ต้องมี prefix ของตัวเอง ไม่อย่างนั้นใช้ตัวนับเดียวกับ throttle:60,1 ของกลุ่ม
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1,auth-register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1,auth-login');
    Route::post('auth/email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:10,1,auth-resend');
    Route::post('auth/email/verify', [AuthController::class, 'confirmEmail'])->middleware('throttle:10,1,auth-verify')->name('api.auth.email.confirm');

    Route::get('catalog', [CatalogController::class, 'catalog'])->name('api.catalog.index');
    Route::get('courses/{slug}', [CatalogController::class, 'showCourse'])->name('api.courses.show');
    Route::get('curriculums/{slug}', [CatalogController::class, 'showCurriculum'])->name('api.curriculums.show');
    Route::get('exams/{slug}', [CatalogController::class, 'showExam'])->name('api.exams.show');
    Route::get('books', [CatalogController::class, 'books'])->name('api.books.index');
    Route::get('books/{slug}', [CatalogController::class, 'showBook'])->name('api.books.show');
    Route::get('banners', [ContentController::class, 'banners'])->name('api.banners.index');
    Route::get('articles', [ContentController::class, 'articles'])->name('api.articles.index');
    Route::get('articles/{slug}', [ContentController::class, 'showArticle'])->name('api.articles.show');
    Route::get('reviews', [ContentController::class, 'reviews'])->name('api.reviews.index');
    // ไม่มีข้อมูลส่วนตัว ลูกค้าที่สั่งซื้อแบบไม่สมัครสมาชิกต้องใช้ public key ของ Omise ด้วย
    Route::get('payment-methods', [PaymentController::class, 'methods']);

    Route::prefix('guest')->group(function () {
        Route::post('checkout/quote', [GuestCheckoutController::class, 'quote'])->middleware('throttle:30,1,guest-quote');
        Route::post('orders', [GuestCheckoutController::class, 'store'])->middleware('throttle:10,1,guest-order');
        Route::get('orders/{token}', [GuestCheckoutController::class, 'show']);
        Route::post('orders/{token}/payment', [GuestCheckoutController::class, 'slip'])->middleware('throttle:10,1,guest-slip');
        Route::post('orders/{token}/payment/thai-qr', [GuestCheckoutController::class, 'thaiQr'])->middleware('throttle:10,1,guest-pay-qr');
        Route::post('orders/{token}/payment/card', [GuestCheckoutController::class, 'card'])->middleware('throttle:10,1,guest-pay-card');
        Route::get('orders/{token}/payment/status', [GuestCheckoutController::class, 'paymentStatus']);
        Route::post('orders/{token}/payment/simulate', [GuestCheckoutController::class, 'simulate'])->middleware('throttle:20,1,guest-pay-simulate');
        Route::get('orders/{token}/receipt', [GuestCheckoutController::class, 'receipt'])->middleware('throttle:20,1,guest-receipt');
    });

    Route::middleware(['auth:sanctum', 'student'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::put('courses/{slug}/videos/{video}/progress', [LearningController::class, 'updateVideoProgress'])
            ->whereNumber('video');

        Route::get('profile', [ProfileController::class, 'show']);
        Route::match(['put', 'post'], 'profile', [ProfileController::class, 'update']);
        Route::put('profile/password', [ProfileController::class, 'updatePassword']);

        Route::post('checkout/quote', [OrderController::class, 'quote']);
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/payment', [OrderController::class, 'pay']);
        Route::get('orders/{order}/receipt', [OrderController::class, 'receipt'])->middleware('throttle:20,1,order-receipt');

        Route::post('orders/{order}/payment-link', [PaymentController::class, 'link']);
        Route::get('orders/{order}/payment/options', [PaymentController::class, 'options']);
        Route::post('orders/{order}/payment/thai-qr', [PaymentController::class, 'thaiQr'])->middleware('throttle:10,1,pay-qr');
        Route::post('orders/{order}/payment/card', [PaymentController::class, 'card'])->middleware('throttle:10,1,pay-card');
        Route::get('orders/{order}/payment/status', [PaymentController::class, 'status']);
        Route::post('orders/{order}/payment/simulate', [PaymentController::class, 'simulate'])->middleware('throttle:20,1,pay-simulate');
    });
});
