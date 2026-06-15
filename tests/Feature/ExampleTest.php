<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /**
     * Test exporting dashboard diagram data requires authentication.
     */
    public function test_dashboard_export_requires_auth(): void
    {
        $response = $this->get('/dashboard/export');
        $response->assertRedirect('/login');
    }

    /**
     * Test exporting DPJP report requires authentication.
     */
    public function test_dpjp_report_export_requires_auth(): void
    {
        $response = $this->get('/dpjp-report/export');
        $response->assertRedirect('/login');
    }

    /**
     * Test exporting dashboard diagram data downloads Excel.
     */
    public function test_dashboard_export_downloads_excel(): void
    {
        $user = User::where('username', 'adminarya')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Admin Arya',
                'username' => 'adminarya',
                'email' => 'adminarya@gmail.com',
                'role' => 'administrator',
                'password' => bcrypt('admin123'),
            ]);
        }

        $response = $this->actingAs($user)->get('/dashboard/export');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('attachment; filename="severity_export_', $response->headers->get('Content-Disposition'));
    }

    /**
     * Test exporting DPJP report downloads Excel.
     */
    public function test_dpjp_report_export_downloads_excel(): void
    {
        $user = User::where('username', 'adminarya')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Admin Arya',
                'username' => 'adminarya',
                'email' => 'adminarya@gmail.com',
                'role' => 'administrator',
                'password' => bcrypt('admin123'),
            ]);
        }

        $response = $this->actingAs($user)->get('/dpjp-report/export');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Test truncating all claim records.
     */
    public function test_truncate_all_deletes_all_claims(): void
    {
        $user = User::where('username', 'adminarya')->first() ?: User::create([
            'name' => 'Admin Arya',
            'username' => 'adminarya',
            'email' => 'adminarya@gmail.com',
            'role' => 'administrator',
            'password' => bcrypt('admin123'),
        ]);

        // Seed a claim record
        \App\Models\ClaimRecord::create([
            'no_rm' => '12345',
            'nama_pasien' => 'Pasien Test',
            'admission_date' => '2026-01-01',
            'discharge_date' => '2026-01-05',
            'inacbg' => 'N-1-40-I',
            'severity' => 'I',
            'dpjp' => 'Dokter Test',
            'total_tarif' => 1000000,
            'tarif_rs' => 800000,
            'selisih' => 200000,
        ]);

        $this->assertEquals(1, \App\Models\ClaimRecord::count());

        $response = $this->actingAs($user)->delete('/claim-records/truncate', [
            'delete_month' => 'all'
        ]);

        $response->assertRedirect('/claim-records');
        $this->assertEquals(0, \App\Models\ClaimRecord::count());
    }

    /**
     * Test deleting claim records for a specific month.
     */
    public function test_truncate_month_deletes_only_that_months_claims(): void
    {
        $user = User::where('username', 'adminarya')->first() ?: User::create([
            'name' => 'Admin Arya',
            'username' => 'adminarya',
            'email' => 'adminarya@gmail.com',
            'role' => 'administrator',
            'password' => bcrypt('admin123'),
        ]);

        // Seed a claim record in Jan 2026
        \App\Models\ClaimRecord::create([
            'no_rm' => '12345',
            'nama_pasien' => 'Pasien Jan',
            'admission_date' => '2026-01-01',
            'discharge_date' => '2026-01-05',
            'inacbg' => 'N-1-40-I',
            'severity' => 'I',
            'dpjp' => 'Dokter Test',
            'total_tarif' => 1000000,
            'tarif_rs' => 800000,
            'selisih' => 200000,
        ]);

        // Seed a claim record in Feb 2026
        \App\Models\ClaimRecord::create([
            'no_rm' => '67890',
            'nama_pasien' => 'Pasien Feb',
            'admission_date' => '2026-02-01',
            'discharge_date' => '2026-02-05',
            'inacbg' => 'N-1-40-I',
            'severity' => 'I',
            'dpjp' => 'Dokter Test',
            'total_tarif' => 1000000,
            'tarif_rs' => 800000,
            'selisih' => 200000,
        ]);

        $this->assertEquals(2, \App\Models\ClaimRecord::count());

        $response = $this->actingAs($user)->delete('/claim-records/truncate', [
            'delete_month' => '2026-01'
        ]);

        $response->assertRedirect('/claim-records');
        $this->assertEquals(1, \App\Models\ClaimRecord::count());
        $this->assertEquals('Pasien Feb', \App\Models\ClaimRecord::first()->nama_pasien);
    }
}
