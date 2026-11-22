<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_screenshot_is_private_and_only_admin_can_view_it(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($customer)->post(route('customer.report.store'), [
            'category' => 'bug', 'description' => 'Ada kendala pada halaman pesanan.',
            'screenshot' => UploadedFile::fake()->image('screen.png'),
        ])->assertSessionHasNoErrors();
        $report = Report::firstOrFail();
        $this->assertStringStartsWith('private-reports/', $report->screenshot_path);
        Storage::disk('local')->assertExists($report->screenshot_path);
        Storage::disk('public')->assertMissing($report->screenshot_path);
        $url = route('admin.reports.screenshot', $report);
        $this->get($url)->assertRedirect(route('customer.dashboard'));
        $response = $this->actingAs($admin)->get($url)->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('admin.reports'))->assertOk()->assertSee($url, false)->assertSee('type="submit" class="status-btn"', false);
    }

    public function test_old_screenshots_remain_visible_to_admin_and_reopening_clears_resolution_date(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::disk('public')->put('reports/old.png', 'legacy screenshot');
        $report = Report::create(['user_id' => $customer->id, 'category' => 'bug', 'description' => 'Contoh laporan lama', 'status' => 'resolved', 'resolved_at' => now(), 'screenshot_path' => 'reports/old.png']);
        $this->actingAs($admin)->get(route('admin.reports.screenshot', $report))->assertOk();
        $this->patch(route('admin.reports.update-status', $report), ['status' => 'open'])->assertSessionHasNoErrors();
        $this->assertNull($report->fresh()->resolved_at);
        $this->patch(route('admin.reports.update-status', $report), ['status' => 'resolved'])->assertSessionHasNoErrors();
        $this->assertNotNull($report->fresh()->resolved_at);
    }
}
