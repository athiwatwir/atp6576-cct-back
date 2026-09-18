<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CourseAssessmentController;
use App\Http\Controllers\CourseChapterController;
use App\Http\Controllers\CourseChapterDocumentController;
use App\Http\Controllers\CourseChapterVideoController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth', 'backend'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('subjects', SubjectController::class)->except(['show']);
    Route::resource('courses', CourseController::class);
    Route::put('courses/{course}/status', [CourseController::class, 'updateStatus'])->name('courses.status');

    Route::post('courses/{course}/chapters', [CourseChapterController::class, 'store'])->name('courses.chapters.store');
    Route::put('courses/{course}/chapters/{chapter}', [CourseChapterController::class, 'update'])->name('courses.chapters.update');
    Route::delete('courses/{course}/chapters/{chapter}', [CourseChapterController::class, 'destroy'])->name('courses.chapters.destroy');

    Route::post('courses/{course}/chapters/{chapter}/videos', [CourseChapterVideoController::class, 'store'])->name('courses.chapters.videos.store');
    Route::put('courses/{course}/chapters/{chapter}/videos/{video}', [CourseChapterVideoController::class, 'update'])->name('courses.chapters.videos.update');
    Route::delete('courses/{course}/chapters/{chapter}/videos/{video}', [CourseChapterVideoController::class, 'destroy'])->name('courses.chapters.videos.destroy');

    Route::post('courses/{course}/chapters/{chapter}/documents', [CourseChapterDocumentController::class, 'store'])->name('courses.chapters.documents.store');
    Route::delete('courses/{course}/chapters/{chapter}/documents/{document}', [CourseChapterDocumentController::class, 'destroy'])->name('courses.chapters.documents.destroy');

    Route::post('courses/{course}/assessments', [CourseAssessmentController::class, 'store'])->name('courses.assessments.store');
    Route::delete('courses/{course}/assessments/{assessment}', [CourseAssessmentController::class, 'destroy'])->name('courses.assessments.destroy');

    // dashboard pages
    Route::get('/', function () {
        return view('pages.dashboard.ecommerce', ['title' => 'E-commerce Dashboard']);
    })->name('dashboard');

    // calender pages
    Route::get('/calendar', function () {
        return view('pages.calender', ['title' => 'Calendar']);
    })->name('calendar');

    // profile pages
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // form pages
    Route::get('/form-elements', function () {
        return view('pages.form.form-elements', ['title' => 'Form Elements']);
    })->name('form-elements');

    // tables pages
    Route::get('/basic-tables', function () {
        return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
    })->name('basic-tables');

    // pages
    Route::get('/blank', function () {
        return view('pages.blank', ['title' => 'Blank']);
    })->name('blank');

    // error pages
    Route::get('/error-404', function () {
        return view('pages.errors.error-404', ['title' => 'Error 404']);
    })->name('error-404');

    // chart pages
    Route::get('/line-chart', function () {
        return view('pages.chart.line-chart', ['title' => 'Line Chart']);
    })->name('line-chart');

    Route::get('/bar-chart', function () {
        return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
    })->name('bar-chart');

    // ui elements pages
    Route::get('/alerts', function () {
        return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
    })->name('alerts');

    Route::get('/avatars', function () {
        return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
    })->name('avatars');

    Route::get('/badge', function () {
        return view('pages.ui-elements.badges', ['title' => 'Badges']);
    })->name('badges');

    Route::get('/buttons', function () {
        return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
    })->name('buttons');

    Route::get('/image', function () {
        return view('pages.ui-elements.images', ['title' => 'Images']);
    })->name('images');

    Route::get('/videos', function () {
        return view('pages.ui-elements.videos', ['title' => 'Videos']);
    })->name('videos');
});
