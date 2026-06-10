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
}
