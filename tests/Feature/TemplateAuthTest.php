<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsApiEnvelope;
use Tests\Support\JwtHelper;
use Tests\TestCase;

class TemplateAuthTest extends TestCase
{
    use RefreshDatabase, JwtHelper, AssertsApiEnvelope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpJwt();
    }

    public function test_health_returns_standardized_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.service', 'template-service')
            ->assertJsonPath('data.status', 'healthy');
    }

    public function test_unauthorized_request_returns_401_envelope(): void
    {
        $response = $this->getJson('/api/v1/templates');

        $this->assertApiError($response, 401, 'AUTH_INVALID');
    }

    public function test_super_admin_can_create_template(): void
    {
        $payload = [
            'key'              => 'welcome_email',
            'name'             => 'Welcome Email',
            'channel'          => 'email',
            'subject'          => 'Hello {{ user_name }}',
            'body'             => 'Hi {{ user_name }}, welcome!',
            'variables_schema' => ['required' => ['user_name']],
            'is_active'        => true,
        ];

        $response = $this->withToken($this->makeToken('super_admin'))
            ->postJson('/api/v1/templates', $payload);

        $this->assertApiSuccess($response, 201);
        $response->assertJsonPath('data.key', 'welcome_email');
    }

    public function test_regular_admin_cannot_create_template(): void
    {
        $payload = [
            'key'              => 'welcome_email',
            'name'             => 'Welcome Email',
            'channel'          => 'email',
            'body'             => 'Hi {{ user_name }}, welcome!',
            'variables_schema' => ['required' => ['user_name']],
        ];

        $response = $this->withToken($this->makeToken('admin'))
            ->postJson('/api/v1/templates', $payload);

        $this->assertApiError($response, 403, 'FORBIDDEN');
    }

    public function test_render_returns_rendered_output_and_correlation(): void
    {
        Template::factory()->create([
            'key'  => 'welcome_email',
            'body' => 'Hi {{ user_name }}',
            'variables_schema' => ['required' => ['user_name']],
        ]);

        $response = $this->withToken($this->makeToken('admin'))
            ->postJson('/api/v1/templates/welcome_email/render', [
                'variables' => ['user_name' => 'Alex'],
            ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.body_rendered', 'Hi Alex');
    }

    public function test_malformed_token_returns_401(): void
    {
        $response = $this->withToken('not-a-valid-jwt')
            ->getJson('/api/v1/templates');

        $this->assertApiError($response, 401, 'AUTH_INVALID');
    }
}
