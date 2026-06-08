<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFeeAccount;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolErpTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $principalRole;
    private Role $clerkRole;
    private User $adminUser;
    private User $principalUser;
    private User $clerkUser;

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
    }

    /**
     * Test promotions access control.
     */
    public function test_promotions_access_control(): void
    {
        // GET /promotions should be visible to Admin and Principal
        $this->actingAs($this->adminUser)
            ->get('/promotions')
            ->assertStatus(200);

        $this->actingAs($this->principalUser)
            ->get('/promotions')
            ->assertStatus(200);

        // POST /promotions should be allowed for Admin
        // (will redirect/validate, but should not return 403)
        $postData = [
            'student_ids' => [1],
            'status' => 'PROMOTED',
            'target_academic_year_id' => 1,
            'target_class_id' => 1,
            'target_section_id' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/promotions', $postData);
        $this->assertNotEquals(403, $response->status());

        // POST /promotions must be strictly forbidden (403) for Principal and Clerk
        $this->actingAs($this->principalUser)
            ->post('/promotions', $postData)
            ->assertStatus(403);

        $this->actingAs($this->clerkUser)
            ->post('/promotions', $postData)
            ->assertStatus(403);
    }

    /**
     * Test ReportController studentReport doesn't crash from missing import.
     */
    public function test_student_report_does_not_crash(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/student-report');

        $this->assertEquals(200, $response->status());
    }

    /**
     * Test receipt number generation.
     */
    public function test_receipt_number_generation(): void
    {
        $ay = AcademicYear::create([
            'year_name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);

        $financeService = resolve(FinanceService::class);

        // Reflection to test protected helper generateReceiptNumber
        $reflection = new \ReflectionClass(FinanceService::class);
        $method = $reflection->getMethod('generateReceiptNumber');
        $method->setAccessible(true);

        $receiptNo1 = $method->invoke($financeService, $ay);
        $this->assertStringStartsWith('RCP-2026-', $receiptNo1);
    }
}
