<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Http\Controllers\StudentExportController;
use App\Http\Controllers\TransferCertificateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "Laravel is working!";
});

Route::get('/students', [StudentController::class, 'index']);
Route::get('/students/create', [StudentController::class, 'create']);
Route::post('/students', [StudentController::class, 'store']);
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
