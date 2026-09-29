<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('courses', [CatalogController::class, 'courses'])->name('api.courses.index');
    Route::get('courses/{slug}', [CatalogController::class, 'showCourse'])->name('api.courses.show');
    Route::get('curriculums', [CatalogController::class, 'curriculums'])->name('api.curriculums.index');
    Route::get('curriculums/{slug}', [CatalogController::class, 'showCurriculum'])->name('api.curriculums.show');
    Route::get('books', [CatalogController::class, 'books'])->name('api.books.index');
    Route::get('books/{slug}', [CatalogController::class, 'showBook'])->name('api.books.show');
    Route::get('banners', [ContentController::class, 'banners'])->name('api.banners.index');
    Route::get('articles', [ContentController::class, 'articles'])->name('api.articles.index');
    Route::get('articles/{slug}', [ContentController::class, 'showArticle'])->name('api.articles.show');

    Route::middleware(['auth:sanctum', 'student'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::match(['put', 'post'], 'profile', [ProfileController::class, 'update']);
        Route::put('profile/password', [ProfileController::class, 'updatePassword']);

        Route::post('checkout/quote', [OrderController::class, 'quote']);
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/payment', [OrderController::class, 'pay']);
    });
});
