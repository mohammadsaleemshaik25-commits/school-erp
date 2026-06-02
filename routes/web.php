<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdmissionRegisterController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BooksFeeController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeAdjustmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Http\Controllers\StudentExportController;
use App\Http\Controllers\TransferCertificateController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/create', [StudentController::class, 'create']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/students/{student}/edit', [StudentController::class, 'edit']);
    Route::put('/students/{student}', [StudentController::class, 'update']);
    Route::get('/students/{student}/history', [StudentController::class, 'history']);
    Route::get('/students/{student}/documents', [StudentDocumentController::class, 'index']);
    Route::post('/students/{student}/documents', [StudentDocumentController::class, 'store']);
    Route::get('/students/{student}/enrollments', [StudentEnrollmentController::class, 'index']);
    Route::post('/students/{student}/enrollments', [StudentEnrollmentController::class, 'store']);
    Route::get('/students/{student}/tc', [TransferCertificateController::class, 'index']);
    Route::post('/students/{student}/tc', [TransferCertificateController::class, 'store']);
    Route::get('/students/{student}/tc/{document}', [TransferCertificateController::class, 'show']);
    Route::get('/students/{student}/id-card', [StudentController::class, 'idCard']);
    Route::get('/students/{student}', [StudentController::class, 'show']);

    Route::get('/students-export/excel', [StudentExportController::class, 'studentsExcel']);
    Route::get('/students-export/pdf', [StudentExportController::class, 'studentsPdf']);
    Route::get('/students-export/passout/excel', [StudentExportController::class, 'passoutExcel']);
    Route::get('/students-export/passout/pdf', [StudentExportController::class, 'passoutPdf']);
    Route::get('/students-export/transferred/excel', [StudentExportController::class, 'transferredExcel']);
    Route::get('/students-export/transferred/pdf', [StudentExportController::class, 'transferredPdf']);

    Route::prefix('api')->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/students', [StudentApiController::class, 'index']);
        Route::get('/students/{student}', [StudentApiController::class, 'show']);
        Route::get('/students/{student}/enrollments', [StudentApiController::class, 'enrollments']);
        Route::get('/enrollments', [StudentApiController::class, 'enrollmentIndex']);
    });

    Route::get('/academic-years', [AcademicYearController::class, 'index']);
    Route::get('/academic-years/create', [AcademicYearController::class, 'create']);
    Route::post('/academic-years', [AcademicYearController::class, 'store']);
    Route::post(
        '/academic-years/{academicYear}/close',
        [AcademicYearController::class, 'closeYear']
    );

    Route::get('/promotions', [PromotionController::class, 'index']);
    Route::post('/promotions', [PromotionController::class, 'store']);

    Route::get('/admissions/register', [AdmissionRegisterController::class, 'index']);

    Route::get('/classes', [ClassController::class, 'index']);
    Route::get('/classes/create', [ClassController::class, 'create']);
    Route::post('/classes', [ClassController::class, 'store']);
    Route::get('/classes/{class}/edit', [ClassController::class, 'edit']);
    Route::put('/classes/{class}', [ClassController::class, 'update']);

    Route::get('/sections', [SectionController::class, 'index']);
    Route::get('/sections/create', [SectionController::class, 'create']);
    Route::post('/sections', [SectionController::class, 'store']);
    Route::get('/sections/{section}/edit', [SectionController::class, 'edit']);
    Route::put('/sections/{section}', [SectionController::class, 'update']);

    Route::get('/fees/collect', [PaymentController::class, 'create'])->name('fees.collect');
    Route::post('/fees/collect', [PaymentController::class, 'store'])->name('fees.payments.store');
    Route::post('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('fees.payments.cancel');

    Route::get('/fees/adjustments', [FeeAdjustmentController::class, 'index'])->name('fees.adjustments.index');
    Route::post('/fees/adjustments', [FeeAdjustmentController::class, 'store']);
    Route::post('/fees/adjustments/{adjustment}/decide', [FeeAdjustmentController::class, 'decide'])->name('fees.adjustments.decide');

    Route::put('/fees/books-fee/{account}', [BooksFeeController::class, 'update'])->name('fees.books.update');

    Route::get('/receipts', [ReceiptController::class, 'index'])->name('fees.receipts.index');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('fees.receipts.show');
    Route::get('/receipts/{receipt}/print', [ReceiptController::class, 'show'])->name('fees.receipts.print');

    Route::get('/student-report', [ReportController::class, 'studentReport'])->name('reports.student');
    Route::get('/fee-report', [ReportController::class, 'feeReport'])->name('reports.fee');
    Route::get('/pending-fees', [ReportController::class, 'pendingFeeReport'])->name('reports.pending');
    Route::get('/daily-collection', [ReportController::class, 'dailyCollection'])->name('reports.daily');
    Route::get('/fees/reports/daily', [ReportController::class, 'dailyCollection'])->name('fees.reports.daily');
    Route::get('/fees/reports/outstanding', [ReportController::class, 'outstandingFees'])->name('fees.reports.outstanding');
    Route::get('/fees/reports/clerk', [ReportController::class, 'clerkCollectionReport'])->name('fees.reports.clerk');

    Route::middleware('role:Administrator,Admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    });
});
