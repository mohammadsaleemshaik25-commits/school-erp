<?php

use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\BooksDecisionController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdmissionRegisterController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BooksFeeController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeAdjustmentController;
use App\Http\Controllers\PaymentController;
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
    // ==========================================
    // 1. CLERK GROUP (Base Access)
    // role: Clerk, Principal, Correspondent, Admin
    // ==========================================
    Route::middleware('role:Clerk,Principal,Correspondent,Admin,Administrator')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/clerk/dashboard', [DashboardController::class, 'index'])->name('clerk.dashboard');

        Route::get('/fees/search-students', [PaymentController::class, 'searchStudents'])->name('fees.search.students');
        Route::get('/fees/finder', [PaymentController::class, 'finder'])->name('fees.finder');
        Route::get('/fees/collect', [PaymentController::class, 'create'])->name('fees.collect');
        Route::post('/fees/collect', [PaymentController::class, 'store'])->name('fees.payments.store');
        Route::post('/fees/previous-fee/{account}/close', [PaymentController::class, 'closePreviousFee'])->name('fees.previous.close');
        Route::post('/fees/previous-fee/{account}/waive', [PaymentController::class, 'waivePreviousFee'])->name('fees.previous.waive');
        Route::get('/fees/ledger/{account}', [PaymentController::class, 'ledger'])->name('fees.ledger');

        Route::get('/receipts', [ReceiptController::class, 'index'])->name('fees.receipts.index');
        Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('fees.receipts.show');
        Route::get('/receipts/{receipt}/print', [ReceiptController::class, 'show'])->name('fees.receipts.print');
        Route::post('/receipts/{receipt}/reprint', [ReceiptController::class, 'reprint'])->name('fees.receipts.reprint');
        Route::post('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('fees.payments.cancel');

        Route::get('/books-decisions', [BooksDecisionController::class, 'index'])->name('books.index');
        Route::get('/books-decisions/{account}/edit', [BooksDecisionController::class, 'edit'])->name('books.edit');
        Route::put('/books-decisions/{account}', [BooksDecisionController::class, 'update'])->name('books.update');
        Route::post('/books-decisions/admission/{admission}', [BooksDecisionController::class, 'finalizeAdmission'])->name('books.finalize');
        Route::get('/books-reports', [BooksDecisionController::class, 'report'])->name('books.report');

        // Promotion Routes
        Route::middleware('role:Admin,Administrator,Principal,Correspondent')->group(function () {
            Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
            Route::get('/promotions/process', [PromotionController::class, 'listStudents'])->name('promotions.process');
            Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store')->middleware('role:Admin,Administrator');
            Route::get('/promotions/report', [PromotionController::class, 'report'])->name('promotions.report');
        });

        Route::get('/fees/adjustments', [FeeAdjustmentController::class, 'index'])->name('fees.adjustments.index');
        Route::get('/fees/adjustments/finder', [FeeAdjustmentController::class, 'finder'])->name('fees.adjustments.finder');
        Route::post('/fees/adjustments', [FeeAdjustmentController::class, 'store'])->name('fees.adjustments.store');
        Route::patch('/fees/books-fee/{accountId}', [BooksFeeController::class, 'update'])->name('fees.books.update');

        Route::get('/students', [StudentController::class, 'index']);
        Route::get('/students/{student}', [StudentController::class, 'show']);
        Route::get('/students/{student}/history', [StudentController::class, 'history']);
        Route::get('/students/{student}/documents', [StudentDocumentController::class, 'index']);
        Route::get('/students/{student}/documents/{document}', [StudentDocumentController::class, 'show']);
        Route::get('/students/{student}/documents/{document}/download', [StudentDocumentController::class, 'download']);
        Route::get('/students/{student}/enrollments', [StudentEnrollmentController::class, 'index']);
        Route::get('/students/{student}/tc/{document}', [TransferCertificateController::class, 'show']);
        Route::get('/students/{student}/id-card', [StudentController::class, 'idCard']);

        Route::get('/student-report', [ReportController::class, 'studentReport'])->name('reports.student');
        Route::get('/fees/reports/daily', [ReportController::class, 'dailyCollection'])->name('fees.reports.daily');
        Route::get('/fees/reports/closing', [ReportController::class, 'clerkDailyClosing'])->name('fees.reports.closing');
        Route::get('/fees/reports/concessions', [ReportController::class, 'concessionReport'])->name('fees.reports.concessions');

        Route::prefix('api')->group(function () {
            Route::get('/students', [StudentApiController::class, 'index']);
            Route::get('/students/{student}', [StudentApiController::class, 'show']);
            Route::get('/students/{student}/enrollments', [StudentApiController::class, 'enrollments']);
            Route::get('/enrollments', [StudentApiController::class, 'enrollmentIndex']);
        });
    });

    // ==========================================
    // 2. PRINCIPAL GROUP (Management Access)
    // role: Principal, Correspondent, Admin
    // ==========================================
    Route::middleware('role:Principal,Correspondent,Admin,Administrator')->group(function () {
        Route::get('/admissions/finder', [AdmissionController::class, 'finder'])->name('admissions.finder');
        Route::get('/admissions/stats', [AdmissionController::class, 'dashboardStats'])->name('admissions.stats');
        
        // Bulk Import Routes
        Route::get('/admissions/bulk', [AdmissionBulkImportController::class, 'index'])->name('admissions.bulk.index');
        Route::get('/admissions/bulk/template', [AdmissionBulkImportController::class, 'downloadTemplate'])->name('admissions.bulk.template');
        Route::post('/admissions/bulk/upload', [AdmissionBulkImportController::class, 'upload'])->name('admissions.bulk.upload');
        Route::post('/admissions/bulk/confirm', [AdmissionBulkImportController::class, 'confirm'])->name('admissions.bulk.confirm');

        // Photo Sync Routes
        Route::get('/admissions/photo-sync', [AdmissionPhotoSyncController::class, 'index'])->name('admissions.photo-sync.index');
        Route::post('/admissions/photo-sync', [AdmissionPhotoSyncController::class, 'sync'])->name('admissions.photo-sync.sync');

        // Transfer Routes
        Route::get('/admissions/transfer', [AdmissionTransferController::class, 'index'])->name('admissions.transfer.index');
        Route::post('/admissions/transfer', [AdmissionTransferController::class, 'process'])->name('admissions.transfer.process');

        Route::resource('admissions', AdmissionController::class);
        Route::post('/admissions/{admission}/approve', [AdmissionController::class, 'approve'])->name('admissions.approve');
        Route::post('/admissions/{admission}/documents', [AdmissionController::class, 'storeDocument'])->name('admissions.documents.store');
        Route::delete('/admissions/documents/{documentId}', [AdmissionController::class, 'deleteDocument'])->name('admissions.documents.destroy');
        Route::get('/admissions/register', [AdmissionRegisterController::class, 'index']);

        Route::get('/students/create', [StudentController::class, 'create']);
        Route::post('/students', [StudentController::class, 'store']);
        Route::get('/students/{student}/edit', [StudentController::class, 'edit']);
        Route::put('/students/{student}', [StudentController::class, 'update']);
        Route::post('/students/{student}/documents', [StudentDocumentController::class, 'store']);
        Route::put('/students/{student}/documents/{document}', [StudentDocumentController::class, 'update']);
        Route::delete('/students/{student}/documents/{document}', [StudentDocumentController::class, 'destroy']);
        Route::post('/students/{student}/enrollments', [StudentEnrollmentController::class, 'store']);
        Route::get('/students/{student}/tc', [TransferCertificateController::class, 'index']);
        Route::post('/students/{student}/tc', [TransferCertificateController::class, 'store']);

        Route::post('/fees/adjustments/{adjustment}/decide', [FeeAdjustmentController::class, 'decide'])->name('fees.adjustments.decide');

        Route::get('/pending-fees', [ReportController::class, 'pendingFeeReport'])->name('reports.pending');
        Route::get('/daily-collection', [ReportController::class, 'dailyCollection'])->name('reports.daily');
        Route::get('/fees/reports/outstanding', [ReportController::class, 'outstandingFees'])->name('fees.reports.outstanding');

        Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');

        Route::get('/students-export/excel', [StudentExportController::class, 'studentsExcel']);
        Route::get('/students-export/pdf', [StudentExportController::class, 'studentsPdf']);
        Route::get('/students-export/passout/excel', [StudentExportController::class, 'passoutExcel']);
        Route::get('/students-export/passout/pdf', [StudentExportController::class, 'passoutPdf']);
        Route::get('/students-export/transferred/excel', [StudentExportController::class, 'transferredExcel']);
        Route::get('/students-export/transferred/pdf', [StudentExportController::class, 'transferredPdf']);
    });

    // ==========================================
    // 3. CORRESPONDENT GROUP (Executive Access)
    // role: Correspondent, Admin
    // ==========================================
    Route::middleware('role:Correspondent,Principal,Admin,Administrator')->group(function () {
        Route::get('/fee-report', [ReportController::class, 'feeReport'])->name('reports.fee');
        Route::get('/fees/reports/clerk', [ReportController::class, 'clerkCollectionReport'])->name('fees.reports.clerk');
        Route::get('/api/dashboard/stats', [DashboardController::class, 'stats']);

        Route::get('/audit-logs', function () {
            return "Audit Logs View (Under Development)";
        })->name('audit.index');
    });

    // ==========================================
    // 4. ADMIN GROUP (System Access)
    // role: Admin, Administrator
    // ==========================================
    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');

        Route::get('/academic-years', [AcademicYearController::class, 'index']);
        Route::get('/academic-years/create', [AcademicYearController::class, 'create']);
        Route::post('/academic-years', [AcademicYearController::class, 'store']);
        Route::post('/academic-years/{academicYear}/close', [AcademicYearController::class, 'closeYear']);

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
    });
});
