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
        $response = $this->get('/dashboard/export/ranap');
        $response->assertRedirect('/login');
    }

    /**
     * Test exporting DPJP report requires authentication.
     */
    public function test_dpjp_report_export_requires_auth(): void
    {
        $response = $this->get('/dpjp-report/export/ranap');
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

        $response = $this->actingAs($user)->get('/dashboard/export/ranap');
        
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

        $response = $this->actingAs($user)->get('/dpjp-report/export/ranap');
        
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

        $response = $this->actingAs($user)->delete('/claim-records/truncate/ranap', [
            'delete_month' => 'all'
        ]);

        $response->assertRedirect('/claim-records/ranap');
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

        $response = $this->actingAs($user)->delete('/claim-records/truncate/ranap', [
            'delete_month' => '2026-01'
        ]);

        $response->assertRedirect('/claim-records/ranap');
        $this->assertEquals(1, \App\Models\ClaimRecord::count());
        $this->assertEquals('Pasien Feb', \App\Models\ClaimRecord::first()->nama_pasien);
    }

    /**
     * Test retrieving patient detail as JSON.
     */
    public function test_show_patient_detail_returns_json(): void
    {
        $user = User::where('username', 'adminarya')->first() ?: User::create([
            'name' => 'Admin Arya',
            'username' => 'adminarya',
            'email' => 'adminarya@gmail.com',
            'role' => 'administrator',
            'password' => bcrypt('admin123'),
        ]);

        $claim = \App\Models\ClaimRecord::create([
            'no_rm' => '12345-RM',
            'nama_pasien' => 'John Doe',
            'admission_date' => '2026-01-01',
            'discharge_date' => '2026-01-05',
            'inacbg' => 'N-1-40-I',
            'severity' => 'I',
            'dpjp' => 'Dr. Smith',
            'ksm' => 'Penyakit Dalam',
            'total_tarif' => 12500000.50,
            'tarif_rs' => 10000000.00,
            'selisih' => 2500000.50,
        ]);

        $response = $this->actingAs($user)->get("/claim-records/{$claim->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'no_rm' => '12345-RM',
            'nama_pasien' => 'John Doe',
            'inacbg' => 'N-1-40-I',
            'severity' => 'I',
            'dpjp' => 'Dr. Smith',
            'ksm' => 'Penyakit Dalam',
            'total_tarif' => 12500000.50,
            'tarif_rs' => 10000000.00,
            'selisih' => 2500000.50,
            'total_tarif_formatted' => 'Rp 12.500.001',
            'tarif_rs_formatted' => 'Rp 10.000.000',
            'selisih_formatted' => 'Rp 2.500.001',
        ]);
    }

    /**
     * Test retrieving patient detail includes raw_data.
     */
    public function test_patient_detail_includes_raw_data(): void
    {
        $user = User::where('username', 'adminarya')->first() ?: User::create([
            'name' => 'Admin Arya',
            'username' => 'adminarya',
            'email' => 'adminarya@gmail.com',
            'role' => 'administrator',
            'password' => bcrypt('admin123'),
        ]);

        $claim = \App\Models\ClaimRecord::create([
            'no_rm' => '99999-RM',
            'nama_pasien' => 'Jane Doe',
            'admission_date' => '2026-01-01',
            'discharge_date' => '2026-01-05',
            'inacbg' => 'N-1-40-I',
            'severity' => 'I',
            'dpjp' => 'Dr. Jones',
            'ksm' => 'Penyakit Dalam',
            'total_tarif' => 5000000.00,
            'tarif_rs' => 4500000.00,
            'selisih' => 500000.00,
            'raw_data' => ['KODE_RS' => '12345', 'NAMA_PASIEN' => 'Jane Doe', 'LOS' => '4'],
        ]);

        $response = $this->actingAs($user)->get("/claim-records/{$claim->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'no_rm' => '99999-RM',
            'raw_data' => [
                'KODE_RS' => '12345',
                'NAMA_PASIEN' => 'Jane Doe',
                'LOS' => '4',
            ]
        ]);
    }

    /**
     * Test sorting on claim records index.
     */
    public function test_claim_records_sorting(): void
    {
        $user = User::where('username', 'adminarya')->first() ?: User::create([
            'name' => 'Admin Arya',
            'username' => 'adminarya',
            'email' => 'adminarya@gmail.com',
            'role' => 'administrator',
            'password' => bcrypt('admin123'),
        ]);

        // Clean table first to ensure strict sorting checks
        \App\Models\ClaimRecord::truncate();

        $claimLow = \App\Models\ClaimRecord::create([
            'no_rm' => '10001-RM',
            'nama_pasien' => 'A Patient',
            'admission_date' => '2026-01-01',
            'discharge_date' => '2026-01-02',
            'inacbg' => 'N-1-40-I',
            'severity' => 'I',
            'dpjp' => 'Dr. Smith',
            'total_tarif' => 1000000.00,
            'tarif_rs' => 900000.00,
            'selisih' => 100000.00,
        ]);

        $claimHigh = \App\Models\ClaimRecord::create([
            'no_rm' => '20002-RM',
            'nama_pasien' => 'Z Patient',
            'admission_date' => '2026-01-01',
            'discharge_date' => '2026-01-05',
            'inacbg' => 'N-1-40-III',
            'severity' => 'III',
            'dpjp' => 'Dr. Jones',
            'total_tarif' => 9000000.00,
            'tarif_rs' => 8000000.00,
            'selisih' => 1000000.00,
        ]);

        // Ascending by total_tarif
        $responseAsc = $this->actingAs($user)->get('/claim-records/ranap?sort_by=total_tarif&sort_dir=asc');
        $responseAsc->assertStatus(200);
        $recordsAsc = $responseAsc->viewData('records');
        $this->assertEquals('A Patient', $recordsAsc[0]->nama_pasien);
        $this->assertEquals('Z Patient', $recordsAsc[1]->nama_pasien);

        // Descending by total_tarif
        $responseDesc = $this->actingAs($user)->get('/claim-records/ranap?sort_by=total_tarif&sort_dir=desc');
        $responseDesc->assertStatus(200);
        $recordsDesc = $responseDesc->viewData('records');
        $this->assertEquals('Z Patient', $recordsDesc[0]->nama_pasien);
        $this->assertEquals('A Patient', $recordsDesc[1]->nama_pasien);
    }

    /**
     * Test DPJP report passes KSM doctor details JSON.
     */
    public function test_dpjp_report_includes_ksm_details_json(): void
    {
        $user = User::where('username', 'adminarya')->first() ?: User::create([
            'name' => 'Admin Arya',
            'username' => 'adminarya',
            'email' => 'adminarya@gmail.com',
            'role' => 'administrator',
            'password' => bcrypt('admin123'),
        ]);

        \App\Models\ClaimRecord::create([
            'no_rm' => '12345-RM',
            'nama_pasien' => 'John Doe',
            'admission_date' => '2026-01-01',
            'discharge_date' => '2026-01-05',
            'inacbg' => 'N-1-40-I',
            'severity' => 'I',
            'dpjp' => 'Dr. Smith',
            'ksm' => 'Penyakit Dalam',
            'total_tarif' => 12500000.00,
            'tarif_rs' => 10000000.00,
            'selisih' => 2500000.00,
        ]);

        $response = $this->actingAs($user)->get('/dpjp-report/ranap');

        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertViewHas('ksmStats');
        $response->assertViewHas('grandTotalPatients', 1);
        $response->assertViewHas('grandTotalTarif', 12500000.00);
        $response->assertViewHas('grandTotalRs', 10000000.00);
        $response->assertViewHas('grandTotalSelisih', 2500000.00);
    }

    /**
     * Test KSM detail report page loads successfully.
     */
    public function test_ksm_report_page_loads_successfully(): void
    {
        $user = User::where('username', 'adminarya')->first() ?: User::create([
            'name' => 'Admin Arya',
            'username' => 'adminarya',
            'email' => 'adminarya@gmail.com',
            'role' => 'administrator',
            'password' => bcrypt('admin123'),
        ]);

        \App\Models\ClaimRecord::create([
            'no_rm' => '12345-RM',
            'nama_pasien' => 'John Doe',
            'admission_date' => '2026-01-01',
            'discharge_date' => '2026-01-05',
            'inacbg' => 'N-1-40-I',
            'severity' => 'I',
            'dpjp' => 'Dr. Smith',
            'ksm' => 'Penyakit Dalam',
            'total_tarif' => 12500000.00,
            'tarif_rs' => 10000000.00,
            'selisih' => 2500000.00,
        ]);

        $response = $this->actingAs($user)->get('/dpjp-report/ksm/ranap/Penyakit Dalam?month=2026-01');

        $response->assertStatus(200);
        $response->assertViewHas('ksm', 'Penyakit Dalam');
        $response->assertViewHas('totalPatients', 1);
        $response->assertViewHas('totalTarif', 12500000.00);
        $response->assertViewHas('totalRs', 10000000.00);
        $response->assertViewHas('totalBalance', 2500000.00);
    }
}
