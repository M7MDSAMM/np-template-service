<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateApiTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKey;

    protected function setUp(): void
    {
        parent::setUp();

        [$private, $public] = $this->generateKeyPair();
        $this->privateKey = $private;

        $dir = storage_path('app/keys');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir.'/jwt-public.pem', $public);

        config([
            'jwt.keys.public' => $dir.'/jwt-public.pem',
            'jwt.issuer'      => 'user-service',
            'jwt.audience'    => 'notification-platform',
        ]);
    }

    public function test_create_template_returns_envelope_and_persists(): void
    {
        $payload = [
            'key'              => 'welcome_email',
            'name'             => 'Welcome Email',
            'channel'          => 'email',
            'subject'          => 'Welcome, {{ user_name }}',
            'body'             => 'Hi {{ user_name }}, your OTP is {{ otp }}',
            'variables_schema' => [
                'required' => ['user_name', 'otp'],
                'optional' => ['support_email'],
                'rules'    => ['otp' => 'string|max:10', 'support_email' => 'email'],
            ],
            'is_active'        => true,
        ];

        $response = $this->withToken($this->makeToken('super_admin'))->postJson('/api/v1/templates', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.key', 'welcome_email')
            ->assertJsonPath('meta', []);

        $this->assertDatabaseHas('templates', ['key' => 'welcome_email', 'version' => 1]);
    }

    public function test_get_template_by_key(): void
    {
        $template = Template::factory()->create(['key' => 'order_shipped']);

        $response = $this->withToken($this->makeToken('super_admin'))->getJson('/api/v1/templates/order_shipped');

        $response->assertOk()
            ->assertJsonPath('data.key', $template->key)
            ->assertJsonPath('meta', []);
    }

    public function test_list_templates_returns_pagination_meta(): void
    {
        Template::factory()->count(3)->create();

        $response = $this->withToken($this->makeToken('super_admin'))->getJson('/api/v1/templates');

        $response->assertOk()
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.total', 3);
    }

    public function test_render_template_success(): void
    {
        Template::factory()->create([
            'key'  => 'welcome_email',
            'body' => 'Hi {{ user_name }}, your OTP is {{ otp }}',
            'variables_schema' => [
                'required' => ['user_name', 'otp'],
            ],
        ]);

        $response = $this->withToken($this->makeToken('admin'))->postJson('/api/v1/templates/welcome_email/render', [
            'variables' => ['user_name' => 'Mohammad', 'otp' => '1234'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.body_rendered', 'Hi Mohammad, your OTP is 1234');
    }

    public function test_render_missing_variable_returns_422(): void
    {
        Template::factory()->create([
            'key'  => 'welcome_email',
            'body' => 'Hi {{ user_name }}, your OTP is {{ otp }}',
            'variables_schema' => [
                'required' => ['user_name', 'otp'],
            ],
        ]);

        $response = $this->withToken($this->makeToken('admin'))->postJson('/api/v1/templates/welcome_email/render', [
            'variables' => ['user_name' => 'Mohammad'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }

    private function makeToken(string $role = 'admin'): string
    {
        $now = time();
        $payload = [
            'iss'  => 'user-service',
            'aud'  => 'notification-platform',
            'sub'  => 'admin-uuid',
            'typ'  => 'admin',
            'role' => $role,
            'iat'  => $now,
            'exp'  => $now + 3600,
        ];

        return JWT::encode($payload, $this->privateKey, 'RS256');
    }

    private function generateKeyPair(): array
    {
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($res, $privateKey);
        $pub = openssl_pkey_get_details($res);
        $publicKey = $pub['key'];

        return [$privateKey, $publicKey];
    }
}
