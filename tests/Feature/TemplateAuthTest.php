<?php

namespace Tests\Feature;

use App\Models\Template;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateAuthTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKey;

    protected function setUp(): void
    {
        parent::setUp();

        [$private, $public] = $this->generateKeyPair();
        $this->privateKey = $private;

        // Pass the PEM content directly via config so tests never touch the
        // production key file at storage/app/keys/jwt-public.pem.
        config([
            'jwt.keys.public_content' => $public,
            'jwt.keys.public'         => null,
            'jwt.issuer'              => 'user-service',
            'jwt.audience'            => 'notification-platform',
        ]);
    }

    public function test_unauthorized_request_returns_401_envelope(): void
    {
        $response = $this->getJson('/api/v1/templates');

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'AUTH_INVALID')
            ->assertJsonStructure(['correlation_id']);
    }

    public function test_super_admin_can_create_template(): void
    {
        $token = $this->makeToken(role: 'super_admin');

        $payload = [
            'key'              => 'welcome_email',
            'name'             => 'Welcome Email',
            'channel'          => 'email',
            'subject'          => 'Hello {{ user_name }}',
            'body'             => 'Hi {{ user_name }}, welcome!',
            'variables_schema' => ['required' => ['user_name']],
            'is_active'        => true,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/templates', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.key', 'welcome_email')
            ->assertJsonStructure(['correlation_id']);
    }

    public function test_render_returns_rendered_output_and_correlation(): void
    {
        Template::factory()->create([
            'key'  => 'welcome_email',
            'body' => 'Hi {{ user_name }}',
            'variables_schema' => ['required' => ['user_name']],
        ]);

        $token = $this->makeToken(role: 'admin');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/templates/welcome_email/render', [
                'variables' => ['user_name' => 'Alex'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.body_rendered', 'Hi Alex')
            ->assertJsonStructure(['correlation_id']);
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
