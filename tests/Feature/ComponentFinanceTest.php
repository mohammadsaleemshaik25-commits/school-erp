<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFeeAccount;
use App\Models\FeeComponent;
use App\Models\ClassFeeComponent;
use App\Models\StudentFeeComponentAccount;
use App\Models\StudentFeeComponentSelection;
use App\Models\FeeWaiver;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\ReceiptComponentDetail;
use App\Models\Role;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\AdmissionService;
use App\Services\BooksDecisionService;
use App\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComponentFinanceTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $principalRole;
    private Role $clerkRole;
    private User $adminUser;
    private User $principalUser;
    private User $clerkUser;

    private AcademicYear $ay;
    private ClassRoom $classRoom;
    private Section $section;
    private \App\Models\FeeStructure $feeStructure;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Roles
        $this->adminRole = Role::firstOrCreate(['role_name' => 'Admin'], ['description' => 'Admin']);
        $this->principalRole = Role::firstOrCreate(['role_name' => 'Principal'], ['description' => 'Principal']);
        $this->clerkRole = Role::firstOrCreate(['role_name' => 'Clerk'], ['description' => 'Clerk']);

        // Seed Users
        $adminId = DB::table('users')->insertGetId([
            'name' => 'Admin User',
            'username' => 'admin',
            'full_name' => 'Admin User',
            'email' => 'admin@test.com',
            'role_id' => $this->adminRole->role_id,
            'password_hash' => bcrypt('password'),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->adminUser = User::find($adminId);

        $principalId = DB::table('users')->insertGetId([
            'name' => 'Principal User',
            'username' => 'principal',
            'full_name' => 'Principal User',
            'email' => 'principal@test.com',
            'role_id' => $this->principalRole->role_id,
            'password_hash' => bcrypt('password'),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->principalUser = User::find($principalId);

        $clerkId = DB::table('users')->insertGetId([
            'name' => 'Clerk User',
            'username' => 'clerk',
            'full_name' => 'Clerk User',
            'email' => 'clerk@test.com',
            'role_id' => $this->clerkRole->role_id,
            'password_hash' => bcrypt('password'),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->clerkUser = User::find($clerkId);

        // Academic Year Setup
        $this->ay = AcademicYear::create([
            'year_name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);

        // Class and Section Setup
        $classId = DB::table('classes')->insertGetId([
            'class_name' => 'Class V',
            'display_order' => 5,
        ]);
        $this->classRoom = ClassRoom::find($classId);

        $sectionId = DB::table('sections')->insertGetId([
            'class_id' => $classId,
            'section_name' => 'A',
        ]);
        $this->section = Section::find($sectionId);

        // Fee Structure Setup
        $this->feeStructure = \App\Models\FeeStructure::create([
            'academic_year_id' => $this->ay->academic_year_id,
            'class_id' => $this->classRoom->class_id,
            'tuition_fee' => 12000.00,
            'books_fee' => 4500.00,
        ]);

        // Class Fee Components Setup
        $tuitionSplit = 4000.00;
        $admissionFee = 500.00;

        $componentPrices = [
            'ADMISSION' => $admissionFee,
            'TERM1' => $tuitionSplit,
            'TERM2' => $tuitionSplit,
            'TERM3' => $tuitionSplit,
            'TEXTBOOK' => 4000.00,
            'NOTEBOOK' => 500.00,
            'EXAM' => 500.00,
            'DIARY' => 500.00,
            'FILE' => 500.00,
            'BELT' => 150.00,
            'TIE' => 100.00,
            'TSHIRT' => 400.00,
            'PREV_TUITION_DUE' => 0.00,
            'PREV_BOOKS_DUE' => 0.00,
            'PREV_ADMISSION_DUE' => 0.00,
        ];

        foreach ($componentPrices as $code => $amt) {
            $comp = FeeComponent::where('component_code', $code)->first();
            if ($comp) {
                ClassFeeComponent::create([
                    'academic_year_id' => $this->ay->academic_year_id,
                    'class_id' => $this->classRoom->class_id,
                    'component_id' => $comp->component_id,
                    'amount' => $amt,
                    'created_at' => now(),
                ]);
            }
        }
    }

    /**
     * Test admission type rules and books selection creating dues.
     */
    public function test_books_selection_creates_dues(): void
    {
        $admissionService = resolve(AdmissionService::class);

        // 1. Create a NEW Student Admission
        $studentData = [
            'student_name' => 'Rohan Dev',
            'dob' => '2016-04-10',
            'gender' => 'Male',
            'nationality' => 'Indian',
            'father_name' => 'F Dev',
            'mother_name' => 'M Dev',
            'pen_no' => 'PEN1234567',
            'aadhaar_no' => '123456789012',
            'phone_primary' => '9876543210',
            'address' => 'Test Address',
            'admission_date' => '2026-06-08',
            'academic_year_id' => $this->ay->academic_year_id,
            'class_id' => $this->classRoom->class_id,
            'section_id' => $this->section->section_id,
            'admission_type' => 'NEW',
            'admission_status' => 'PENDING',
        ];

        $admission = $this->createTestAdmission($studentData, $this->adminUser->user_id);
        
        // Approve admission
        $admission = $admissionService->approveAdmission($admission->admission_id, $this->adminUser->user_id);

        // Finalize Admission with TEXTBOOK and NOTEBOOK selected
        $admissionService->finalizeAdmission(
            $admission->admission_id,
            'SCHOOL',
            $this->adminUser->user_id,
            ['TEXTBOOK', 'NOTEBOOK']
        );

        $student = Student::where('student_name', 'Rohan Dev')->first();
        $this->assertNotNull($student);
        $this->assertEquals('NEW', $student->admission_type);

        $enrollment = StudentEnrollment::where('student_id', $student->student_id)->first();
        $this->assertNotNull($enrollment);

        // Verify that component accounts were created
        $componentAccounts = StudentFeeComponentAccount::where('enrollment_id', $enrollment->enrollment_id)->get();
        
        // Expected components: ADMISSION, TERM1, TERM2, TERM3, TEXTBOOK, NOTEBOOK
        $activeCodes = $componentAccounts->map(fn($acc) => $acc->component->component_code)->toArray();
        $this->assertContains('ADMISSION', $activeCodes);
        $this->assertContains('TERM1', $activeCodes);
        $this->assertContains('TERM2', $activeCodes);
        $this->assertContains('TERM3', $activeCodes);
        $this->assertContains('TEXTBOOK', $activeCodes);
        $this->assertContains('NOTEBOOK', $activeCodes);

        // EXAM and other optional books components should NOT be generated
        $this->assertNotContains('EXAM', $activeCodes);
        $this->assertNotContains('BELT', $activeCodes);

        // Verify legacy account sync
        $legacyAccount = StudentFeeAccount::where('enrollment_id', $enrollment->enrollment_id)->first();
        $this->assertNotNull($legacyAccount);
        $this->assertEquals('SCHOOL', $legacyAccount->books_status);
        $this->assertEquals(4500.00, $legacyAccount->books_fee_applied); // TEXTBOOK(4000) + NOTEBOOK(500)
        $this->assertEquals(12500.00, $legacyAccount->final_tuition_fee); // TERM1(4000) + TERM2(4000) + TERM3(4000) + ADMISSION(500)
    }

    /**
     * Test partial payments update balance and status.
     */
    public function test_partial_payments_work(): void
    {
        $admissionService = resolve(AdmissionService::class);
        $financeService = resolve(FinanceService::class);

        $studentData = [
            'student_name' => 'Rahul Dev',
            'dob' => '2016-04-10',
            'gender' => 'Male',
            'father_name' => 'F Dev',
            'mother_name' => 'M Dev',
            'pen_no' => 'PEN1234568',
            'aadhaar_no' => '123456789013',
            'phone_primary' => '9876543210',
            'address' => 'Test Address',
            'admission_date' => '2026-06-08',
            'academic_year_id' => $this->ay->academic_year_id,
            'class_id' => $this->classRoom->class_id,
            'section_id' => $this->section->section_id,
            'admission_type' => 'NEW',
        ];

        $admission = $this->createTestAdmission($studentData, $this->adminUser->user_id);
        $admission = $admissionService->approveAdmission($admission->admission_id, $this->adminUser->user_id);
        $admissionService->finalizeAdmission($admission->admission_id, 'SCHOOL', $this->adminUser->user_id, ['TEXTBOOK', 'NOTEBOOK']);

        $student = Student::where('student_name', 'Rahul Dev')->first();
        $enrollment = StudentEnrollment::where('student_id', $student->student_id)->first();
        $legacyAccount = StudentFeeAccount::where('enrollment_id', $enrollment->enrollment_id)->first();

        $term1Account = StudentFeeComponentAccount::where('enrollment_id', $enrollment->enrollment_id)
            ->whereHas('component', fn($q) => $q->where('component_code', 'TERM1'))
            ->first();

        // 1. Pay ₹2,000 partially towards TERM1 (Billed: ₹4,000)
        $paymentData = [
            'account_id' => $legacyAccount->account_id,
            'amount' => 2000.00,
            'payment_mode' => 'CASH',
            'allocations' => [
                $term1Account->id => 2000.00,
            ],
        ];

        $payment = $financeService->collectPayment($paymentData, $this->clerkUser->user_id);
        
        $this->assertNotNull($payment->receipt);
        $this->assertEquals(2000.00, $payment->amount);

        // Verify component account status
        $term1Account->refresh();
        $this->assertEquals(2000.00, $term1Account->paid_amount);
        $this->assertEquals(2000.00, $term1Account->balance_amount);
        $this->assertEquals('PARTIALLY_PAID', $term1Account->status);

        // Verify legacy account aggregate sync
        $legacyAccount->refresh();
        $this->assertEquals(2000.00, $legacyAccount->total_paid);
        $this->assertEquals('PARTIALLY_PAID', $legacyAccount->status);

        // Verify receipt details
        $receiptDetails = ReceiptComponentDetail::where('receipt_id', $payment->receipt->receipt_id)->get();
        $this->assertCount(1, $receiptDetails);
        $this->assertEquals('Term-1 Tuition Fee', $receiptDetails->first()->component_name);
        $this->assertEquals(4000.00, $receiptDetails->first()->previous_balance);
        $this->assertEquals(2000.00, $receiptDetails->first()->paid_amount);
        $this->assertEquals(2000.00, $receiptDetails->first()->remaining_balance);

        // 2. Validate Overpayment Prevention
        $this->expectException(\InvalidArgumentException::class);
        $financeService->collectPayment([
            'account_id' => $legacyAccount->account_id,
            'amount' => 3000.00,
            'payment_mode' => 'CASH',
            'allocations' => [
                $term1Account->id => 3000.00, // Exceeds remaining 2,000 balance!
            ],
        ], $this->clerkUser->user_id);
    }

    /**
     * Test waivers require Principal/Correspondent approval.
     */
    public function test_waivers_require_approval_and_work(): void
    {
        $admissionService = resolve(AdmissionService::class);
        $financeService = resolve(FinanceService::class);

        $studentData = [
            'student_name' => 'Vijay Dev',
            'dob' => '2016-04-10',
            'gender' => 'Male',
            'father_name' => 'F Dev',
            'mother_name' => 'M Dev',
            'pen_no' => 'PEN1234569',
            'aadhaar_no' => '123456789014',
            'phone_primary' => '9876543210',
            'address' => 'Test Address',
            'admission_date' => '2026-06-08',
            'academic_year_id' => $this->ay->academic_year_id,
            'class_id' => $this->classRoom->class_id,
            'section_id' => $this->section->section_id,
            'admission_type' => 'NEW',
        ];

        $admission = $this->createTestAdmission($studentData, $this->adminUser->user_id);
        $admission = $admissionService->approveAdmission($admission->admission_id, $this->adminUser->user_id);
        $admissionService->finalizeAdmission($admission->admission_id, 'SCHOOL', $this->adminUser->user_id, ['TEXTBOOK', 'NOTEBOOK']);

        $student = Student::where('student_name', 'Vijay Dev')->first();
        $enrollment = StudentEnrollment::where('student_id', $student->student_id)->first();
        $legacyAccount = StudentFeeAccount::where('enrollment_id', $enrollment->enrollment_id)->first();

        $textbookAccount = StudentFeeComponentAccount::where('enrollment_id', $enrollment->enrollment_id)
            ->whereHas('component', fn($q) => $q->where('component_code', 'TEXTBOOK'))
            ->first();

        // 1. Clerk trying to apply waiver should FAIL
        try {
            $financeService->applyWaiver([
                'student_id' => $student->student_id,
                'enrollment_id' => $enrollment->enrollment_id,
                'component_id' => $textbookAccount->component_id,
                'waiver_amount' => 1000.00,
                'reason' => 'Financial Need',
            ], $this->clerkUser->user_id);
            $this->fail('Clerk was able to approve a waiver!');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Unauthorized', $e->getMessage());
        }

        // 2. Principal applying waiver should SUCCEED
        $financeService->applyWaiver([
            'student_id' => $student->student_id,
            'enrollment_id' => $enrollment->enrollment_id,
            'component_id' => $textbookAccount->component_id,
            'waiver_amount' => 1000.00,
            'reason' => 'Financial Need',
        ], $this->principalUser->user_id);

        $textbookAccount->refresh();
        $this->assertEquals(1000.00, $textbookAccount->waiver_amount);
        $this->assertEquals(3000.00, $textbookAccount->balance_amount); // 4000 - 1000 = 3000

        // Verify Audit Log and Waiver Table entries
        $waiver = FeeWaiver::where('enrollment_id', $enrollment->enrollment_id)->first();
        $this->assertNotNull($waiver);
        $this->assertEquals(1000.00, $waiver->waiver_amount);

        // Verify legacy sync
        $legacyAccount->refresh();
        $this->assertEquals(1000.00, $legacyAccount->waived_amount);
    }

    /**
     * Test concessions requested, approved, and applied.
     */
    public function test_concessions_work(): void
    {
        $admissionService = resolve(AdmissionService::class);
        $financeService = resolve(FinanceService::class);

        $studentData = [
            'student_name' => 'Karan Dev',
            'dob' => '2016-04-10',
            'gender' => 'Male',
            'father_name' => 'F Dev',
            'mother_name' => 'M Dev',
            'pen_no' => 'PEN1234570',
            'aadhaar_no' => '123456789015',
            'phone_primary' => '9876543210',
            'address' => 'Test Address',
            'admission_date' => '2026-06-08',
            'academic_year_id' => $this->ay->academic_year_id,
            'class_id' => $this->classRoom->class_id,
            'section_id' => $this->section->section_id,
            'admission_type' => 'NEW',
        ];

        $admission = $this->createTestAdmission($studentData, $this->adminUser->user_id);
        $admission = $admissionService->approveAdmission($admission->admission_id, $this->adminUser->user_id);
        $admissionService->finalizeAdmission($admission->admission_id, 'SCHOOL', $this->adminUser->user_id, ['TEXTBOOK', 'NOTEBOOK']);

        $student = Student::where('student_name', 'Karan Dev')->first();
        $enrollment = StudentEnrollment::where('student_id', $student->student_id)->first();
        $legacyAccount = StudentFeeAccount::where('enrollment_id', $enrollment->enrollment_id)->first();

        $term1Account = StudentFeeComponentAccount::where('enrollment_id', $enrollment->enrollment_id)
            ->whereHas('component', fn($q) => $q->where('component_code', 'TERM1'))
            ->first();

        // 1. Clerk requests a concession of ₹500 on TERM1 Tuition Component
        $adjustment = $financeService->requestAdjustment([
            'account_id' => $legacyAccount->account_id,
            'component_id' => $term1Account->component_id,
            'adjustment_type' => 'CONCESSION',
            'discount_amount' => 500.00,
            'reason' => 'Sibling discount',
        ], $this->clerkUser->user_id);

        $this->assertEquals('PENDING', $adjustment->approval_status);

        // 2. Admin approves concession
        $financeService->decideAdjustment($adjustment->adjustment_id, 'APPROVED', 'Approved by admin', $this->adminUser->user_id);

        $term1Account->refresh();
        $this->assertEquals(500.00, $term1Account->concession_amount);
        $this->assertEquals(3500.00, $term1Account->balance_amount); // 4000 - 500 = 3500

        // Verify legacy sync
        $legacyAccount->refresh();
        $this->assertEquals(500.00, $legacyAccount->discount_amount);
    }

    /**
     * Test carry forward outstanding balance into new year components.
     */
    public function test_promotion_carry_forward(): void
    {
        $admissionService = resolve(AdmissionService::class);
        $financeService = resolve(FinanceService::class);
        $promotionService = resolve(PromotionService::class);

        // 1. Create Student in current year
        $studentData = [
            'student_name' => 'Abhay Dev',
            'dob' => '2016-04-10',
            'gender' => 'Male',
            'father_name' => 'F Dev',
            'mother_name' => 'M Dev',
            'pen_no' => 'PEN1234571',
            'aadhaar_no' => '123456789016',
            'phone_primary' => '9876543210',
            'address' => 'Test Address',
            'admission_date' => '2026-06-08',
            'academic_year_id' => $this->ay->academic_year_id,
            'class_id' => $this->classRoom->class_id,
            'section_id' => $this->section->section_id,
            'admission_type' => 'TRANSFER',
        ];

        $admission = $this->createTestAdmission($studentData, $this->adminUser->user_id);
        $admission = $admissionService->approveAdmission($admission->admission_id, $this->adminUser->user_id);
        $admissionService->finalizeAdmission($admission->admission_id, 'SCHOOL', $this->adminUser->user_id, ['TEXTBOOK']);

        $student = Student::where('student_name', 'Abhay Dev')->first();
        $enrollment = StudentEnrollment::where('student_id', $student->student_id)->first();
        $legacyAccount = StudentFeeAccount::where('enrollment_id', $enrollment->enrollment_id)->first();

        // Pay TERM1 and TERM2 fully (Leaves TERM3 ₹4,000 outstanding, TEXTBOOK ₹4,000 outstanding)
        $term1Account = StudentFeeComponentAccount::where('enrollment_id', $enrollment->enrollment_id)
            ->whereHas('component', fn($q) => $q->where('component_code', 'TERM1'))->first();
        $term2Account = StudentFeeComponentAccount::where('enrollment_id', $enrollment->enrollment_id)
            ->whereHas('component', fn($q) => $q->where('component_code', 'TERM2'))->first();

        $financeService->collectPayment([
            'account_id' => $legacyAccount->account_id,
            'amount' => 8000.00,
            'payment_mode' => 'CASH',
            'allocations' => [
                $term1Account->id => 4000.00,
                $term2Account->id => 4000.00,
            ],
        ], $this->clerkUser->user_id);

        // 2. Setup NEXT Academic Year
        $nextAy = AcademicYear::create([
            'year_name' => '2027-2028',
            'start_date' => '2027-06-01',
            'end_date' => '2028-05-31',
            'is_active' => false,
        ]);

        $nextClassId = DB::table('classes')->insertGetId([
            'class_name' => 'Class VI',
            'display_order' => 6,
        ]);
        $nextClass = ClassRoom::find($nextClassId);

        $nextSectionId = DB::table('sections')->insertGetId([
            'class_id' => $nextClassId,
            'section_name' => 'A',
        ]);
        $nextSection = Section::find($nextSectionId);

        // Define Class VI components in next year
        $nextComponents = ['TERM1' => 4500.00, 'TERM2' => 4500.00, 'TERM3' => 4500.00, 'PREV_TUITION_DUE' => 0.00, 'PREV_BOOKS_DUE' => 0.00, 'PREV_ADMISSION_DUE' => 0.00];
        foreach ($nextComponents as $code => $amt) {
            $comp = FeeComponent::where('component_code', $code)->first();
            if ($comp) {
                ClassFeeComponent::create([
                    'academic_year_id' => $nextAy->academic_year_id,
                    'class_id' => $nextClass->class_id,
                    'component_id' => $comp->component_id,
                    'amount' => $amt,
                    'created_at' => now(),
                ]);
            }
        }
        
        \App\Models\FeeStructure::create([
            'academic_year_id' => $nextAy->academic_year_id,
            'class_id' => $nextClass->class_id,
            'tuition_fee' => 13500.00,
            'books_fee' => 0.00,
        ]);

        $promotionService->promoteStudent([
            'student_id' => $student->student_id,
            'old_enrollment_id' => $enrollment->enrollment_id,
            'status' => 'PROMOTED',
            'target_academic_year_id' => $nextAy->academic_year_id,
            'target_class_id' => $nextClass->class_id,
            'target_section_id' => $nextSection->section_id,
        ], $this->adminUser->user_id);

        // 4. Verify Carry Forward components on the new enrollment
        $newEnrollment = StudentEnrollment::where('student_id', $student->student_id)
            ->where('academic_year_id', $nextAy->academic_year_id)
            ->first();

        $prevTuitionAcc = StudentFeeComponentAccount::where('enrollment_id', $newEnrollment->enrollment_id)
            ->whereHas('component', fn($q) => $q->where('component_code', 'PREV_TUITION_DUE'))
            ->first();

        $prevBooksAcc = StudentFeeComponentAccount::where('enrollment_id', $newEnrollment->enrollment_id)
            ->whereHas('component', fn($q) => $q->where('component_code', 'PREV_BOOKS_DUE'))
            ->first();

        $this->assertNotNull($prevTuitionAcc);
        $this->assertEquals(4000.00, $prevTuitionAcc->amount); // TERM3 outstanding (4000) + ADMISSION outstanding (0) = 4000

        $this->assertNotNull($prevBooksAcc);
        $this->assertEquals(4000.00, $prevBooksAcc->amount); // TEXTBOOK outstanding (4000)

        // Verify legacy previous balance is populated
        $nextLegacyAccount = StudentFeeAccount::where('enrollment_id', $newEnrollment->enrollment_id)->first();
        $this->assertEquals(8000.00, $nextLegacyAccount->previous_balance);
        $this->assertEquals(21500.00, $nextLegacyAccount->total_due); // Tuition(13500) + Prev(8000)
    }

    /**
     * Test legacy fee payment works if student has no components.
     */
    public function test_legacy_payments_still_work(): void
    {
        $financeService = resolve(FinanceService::class);

        // Create a student manually and enrollment without component accounts
        $student = Student::create([
            'admission_no' => 'ADM999',
            'student_name' => 'Legacy Student',
            'dob' => '2016-04-10',
            'gender' => 'Male',
            'father_name' => 'F Dev',
            'mother_name' => 'M Dev',
            'pen_no' => 'PEN9999999',
            'aadhaar_no' => '999999999999',
            'phone_primary' => '9876543210',
            'address' => 'Test Address',
            'status' => 'ACTIVE',
            'admission_date' => '2026-06-08',
        ]);

        $enrollment = StudentEnrollment::create([
            'student_id' => $student->student_id,
            'academic_year_id' => $this->ay->academic_year_id,
            'class_id' => $this->classRoom->class_id,
            'section_id' => $this->section->section_id,
            'promotion_status' => 'NEW',
            'status' => 'ACTIVE',
        ]);

        $legacyAccount = StudentFeeAccount::create([
            'enrollment_id' => $enrollment->enrollment_id,
            'fee_structure_id' => $this->feeStructure->fee_structure_id,
            'discount_amount' => 0,
            'final_tuition_fee' => 12000.00,
            'books_status' => 'SCHOOL',
            'books_from_school' => true,
            'books_fee_applied' => 4500.00,
            'books_fee' => 4500.00,
            'net_fee' => 16500.00,
            'previous_balance' => 0,
            'waived_amount' => 0,
            'total_due' => 16500.00,
            'status' => 'UNPAID',
        ]);

        // Process a payment through legacy path (no allocations sent!)
        $paymentData = [
            'account_id' => $legacyAccount->account_id,
            'amount' => 5000.00,
            'payment_mode' => 'CASH',
            'allocation' => 'TUITION',
        ];

        $payment = $financeService->collectPayment($paymentData, $this->clerkUser->user_id);

        $this->assertNotNull($payment);
        $this->assertEquals(5000.00, $payment->amount);
        $this->assertEquals(5000.00, $payment->tuition_fee_paid);
        $this->assertEquals(0.00, $payment->books_fee_paid);

        $legacyAccount->refresh();
        $this->assertEquals(5000.00, $legacyAccount->total_paid);
        $this->assertEquals(11500.00, $legacyAccount->remaining_balance);
    }

    private function createTestAdmission(array $data, int $userId): \App\Models\Admission
    {
        $admissionService = resolve(AdmissionService::class);
        $data['cropped_photo_path'] = 'photos/test.jpg';
        $admission = $admissionService->createAdmission($data, $userId);

        DB::table('student_documents')->insert([
            'student_id' => $admission->student_id,
            'document_type' => 'STUDENT_AADHAAR',
            'file_name' => 'aadhaar.pdf',
            'file_path' => 'documents/aadhaar.pdf',
            'uploaded_at' => now(),
            'verification_status' => 'UPLOADED',
        ]);

        return $admission;
    }
}
