<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PwaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_manifest_webmanifest_is_valid_json_and_contains_required_fields(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $this->assertFileExists($manifestPath);

        $json = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($json);
        $this->assertSame('eHadir — Sistem Kehadiran Organisasi', $json['name']);
        $this->assertSame('eHadir', $json['short_name']);
        $this->assertSame('standalone', $json['display']);
        $this->assertSame('/?source=pwa', $json['start_url']);
        $this->assertSame('/', $json['scope']);
        $this->assertSame('#059669', $json['theme_color']);
        $this->assertSame('#020617', $json['background_color']);

        // Check icons
        $this->assertNotEmpty($json['icons']);
        foreach ($json['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }

        // Check shortcuts
        $this->assertNotEmpty($json['shortcuts']);
    }

    public function test_service_worker_file_exists_in_public(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);
        $this->assertStringContainsString('ehadir-static-v', $content);
        $this->assertStringContainsString('/offline', $content);
    }

    public function test_offline_page_route_returns_status_200(): void
    {
        $response = $this->get('/offline');
        $response->assertStatus(200);
        $response->assertSee('Tiada Sambungan Internet');
        $response->assertSee('Cuba Semula Sekarang');
    }

    public function test_login_layout_renders_pwa_head_meta_and_apple_tags(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('manifest.webmanifest');
        $response->assertSee('apple-mobile-web-app-capable');
        $response->assertSee('apple-touch-icon.png');
        $response->assertSee('viewport-fit=cover');
    }

    public function test_authenticated_dashboard_renders_pwa_head_meta(): void
    {
        $user = User::create([
            'nama' => 'PWA Admin',
            'email' => 'pwa@kejora.gov.my',
            'password_hash' => Hash::make('password'),
            'aktif' => true,
        ]);
        $user->roles()->attach(Role::where('kod', 'SUPERADMIN')->first());

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('manifest.webmanifest');
        $response->assertSee('apple-mobile-web-app-title');
        $response->assertSee('eHadir');
    }
}
