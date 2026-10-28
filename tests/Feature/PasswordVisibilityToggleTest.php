<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PasswordVisibilityToggleTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        View::share('errors', new ViewErrorBag());

        $superAdminRole = Role::where('kod', 'SUPERADMIN')->first();

        $this->superadmin = User::create([
            'nama' => 'Super Administrator',
            'email' => 'admin@kejora.gov.my',
            'password_hash' => Hash::make('password123'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach($superAdminRole);
    }

    public function test_login_page_renders_password_visibility_toggle(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('data-password-toggle', false);
        $response->assertSee('data-password-input', false);
        $response->assertSee('x-data="{ showPassword: false }"', false);
        $response->assertSee(':type="showPassword ? \'text\' : \'password\'"', false);
        $response->assertSee('data-icon="eye"', false);
        $response->assertSee('data-icon="eye-slash"', false);
    }

    public function test_user_creation_page_renders_password_visibility_toggle(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('users.create'));

        $response->assertStatus(200);
        $response->assertSee('data-password-toggle', false);
        $response->assertSee('data-password-input', false);
        $response->assertSee('id="password"', false);
    }

    public function test_user_edit_page_renders_password_visibility_toggle(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('users.edit', $this->superadmin));

        $response->assertStatus(200);
        $response->assertSee('data-password-toggle', false);
        $response->assertSee('data-password-input', false);
    }

    public function test_profile_page_renders_toggles_for_all_password_fields(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('profile.edit'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 3 password fields: current_password, password, password_confirmation
        $this->assertEquals(3, substr_count($content, 'data-password-toggle'));
        $this->assertEquals(3, substr_count($content, 'data-password-input'));
        $this->assertStringContainsString('id="current_password"', $content);
        $this->assertStringContainsString('id="password"', $content);
        $this->assertStringContainsString('id="password_confirmation"', $content);
    }

    public function test_dedicated_password_input_component_renders_correctly(): void
    {
        $html = Blade::render('<x-form.password-input name="secret_code" label="Kod Rahsia" />');

        $this->assertStringContainsString('Kod Rahsia', $html);
        $this->assertStringContainsString('name="secret_code"', $html);
        $this->assertStringContainsString('id="secret_code"', $html);
        $this->assertStringContainsString('data-password-toggle', $html);
        $this->assertStringContainsString('data-password-input', $html);
    }

    public function test_toggleable_false_disables_toggle_button(): void
    {
        $html = Blade::render('<x-form.input type="password" name="pin" label="PIN" :toggleable="false" />');

        $this->assertStringContainsString('name="pin"', $html);
        $this->assertStringNotContainsString('data-password-toggle', $html);
        $this->assertStringNotContainsString('x-data="{ showPassword: false }"', $html);
    }
}

