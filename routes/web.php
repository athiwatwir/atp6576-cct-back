<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CourseAssessmentController;
use App\Http\Controllers\CourseAssessmentQuestionController;
use App\Http\Controllers\CourseChapterController;
use App\Http\Controllers\CourseChapterDocumentController;
use App\Http\Controllers\CourseChapterVideoController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamQuestionController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\OrderController;
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

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('instructors', InstructorController::class)->except(['show']);
    Route::resource('subjects', SubjectController::class)->except(['show']);
    Route::resource('courses', CourseController::class);
    Route::put('courses/{course}/status', [CourseController::class, 'updateStatus'])->name('courses.status');
    Route::get('courses/{course}/deletion-summary', [CourseController::class, 'deletionSummary'])->name('courses.deletion-summary');
    Route::post('courses/{course}/delete-step', [CourseController::class, 'deleteStep'])->name('courses.delete-step');

    Route::resource('exams', ExamController::class)->parameters(['exams' => 'assessment']);
    Route::post('exams/{assessment}/questions', [ExamQuestionController::class, 'store'])->name('exams.questions.store');
    Route::put('exams/{assessment}/questions/{question}', [ExamQuestionController::class, 'update'])->name('exams.questions.update');
    Route::delete('exams/{assessment}/questions/{question}', [ExamQuestionController::class, 'destroy'])->name('exams.questions.destroy');

    Route::resource('books', BookController::class)->parameters(['books' => 'product']);
    Route::resource('curriculums', CurriculumController::class);
    Route::get('curriculums/{curriculum}/catalog', [CurriculumController::class, 'catalog'])->name('curriculums.catalog');
    Route::get('curriculums/{curriculum}/attached', [CurriculumController::class, 'attached'])->name('curriculums.attached');
    Route::post('curriculums/{curriculum}/items', [CurriculumController::class, 'attachItems'])->name('curriculums.items.store');
    Route::delete('curriculums/{curriculum}/items', [CurriculumController::class, 'detachItem'])->name('curriculums.items.destroy');
    Route::resource('orders', OrderController::class);
    Route::put('orders/{order}/shipping-status', [OrderController::class, 'updateShippingStatus'])->name('orders.shipping-status');
    Route::put('orders/{order}/tracking', [OrderController::class, 'updateTracking'])->name('orders.tracking');
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');

    Route::post('courses/{course}/chapters', [CourseChapterController::class, 'store'])->name('courses.chapters.store');
    Route::put('courses/{course}/chapters/reorder', [CourseChapterController::class, 'reorder'])->name('courses.chapters.reorder');
    Route::put('courses/{course}/chapters/{chapter}', [CourseChapterController::class, 'update'])->name('courses.chapters.update');
    Route::delete('courses/{course}/chapters/{chapter}', [CourseChapterController::class, 'destroy'])->name('courses.chapters.destroy');

    Route::post('courses/{course}/chapters/{chapter}/videos', [CourseChapterVideoController::class, 'store'])->name('courses.chapters.videos.store');
    Route::put('courses/{course}/chapters/{chapter}/videos/{video}', [CourseChapterVideoController::class, 'update'])->name('courses.chapters.videos.update');
    Route::delete('courses/{course}/chapters/{chapter}/videos/{video}', [CourseChapterVideoController::class, 'destroy'])->name('courses.chapters.videos.destroy');

    Route::post('courses/{course}/chapters/{chapter}/documents', [CourseChapterDocumentController::class, 'store'])->name('courses.chapters.documents.store');
    Route::delete('courses/{course}/chapters/{chapter}/documents/{document}', [CourseChapterDocumentController::class, 'destroy'])->name('courses.chapters.documents.destroy');

    Route::post('courses/{course}/assessments', [CourseAssessmentController::class, 'store'])->name('courses.assessments.store');
    Route::put('courses/{course}/assessments/{assessment}', [CourseAssessmentController::class, 'update'])->name('courses.assessments.update');
    Route::delete('courses/{course}/assessments/{assessment}', [CourseAssessmentController::class, 'destroy'])->name('courses.assessments.destroy');

    Route::post('courses/{course}/assessments/{assessment}/questions', [CourseAssessmentQuestionController::class, 'store'])->name('courses.assessments.questions.store');
    Route::put('courses/{course}/assessments/{assessment}/questions/{question}', [CourseAssessmentQuestionController::class, 'update'])->name('courses.assessments.questions.update');
    Route::delete('courses/{course}/assessments/{assessment}/questions/{question}', [CourseAssessmentQuestionController::class, 'destroy'])->name('courses.assessments.questions.destroy');

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
