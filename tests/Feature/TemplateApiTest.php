<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsApiEnvelope;
use Tests\Support\JwtHelper;
use Tests\TestCase;

class TemplateApiTest extends TestCase
{
    use RefreshDatabase, JwtHelper, AssertsApiEnvelope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpJwt();
    }

    // ── Create ──────────────────────────────────────────────────────────

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

        $response = $this->withToken($this->makeToken('super_admin'))
            ->postJson('/api/v1/templates', $payload);

        $this->assertApiSuccess($response, 201);
        $response->assertJsonPath('data.key', 'welcome_email');
        $this->assertDatabaseHas('templates', ['key' => 'welcome_email', 'version' => 1]);
    }

    public function test_create_validation_rejects_missing_fields(): void
    {
        $response = $this->withToken($this->makeToken('super_admin'))
            ->postJson('/api/v1/templates', []);

        $this->assertApiError($response, 422, 'VALIDATION_ERROR');
        $response->assertJsonValidationErrors(['key', 'name', 'channel', 'body']);
    }

    public function test_create_duplicate_key_rejected(): void
    {
        Template::factory()->create(['key' => 'existing_key']);

        $response = $this->withToken($this->makeToken('super_admin'))
            ->postJson('/api/v1/templates', [
                'key'              => 'existing_key',
                'name'             => 'Duplicate',
                'channel'          => 'email',
                'body'             => 'Body',
                'variables_schema' => [],
            ]);

        $this->assertApiError($response, 422, 'VALIDATION_ERROR');
        $response->assertJsonValidationErrors(['key']);
    }

    // ── Read ────────────────────────────────────────────────────────────

    public function test_get_template_by_key(): void
    {
        $template = Template::factory()->create(['key' => 'order_shipped']);

        $response = $this->withToken($this->makeToken('super_admin'))
            ->getJson('/api/v1/templates/order_shipped');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.key', $template->key);
    }

    public function test_get_nonexistent_template_returns_404(): void
    {
        $response = $this->withToken($this->makeToken('super_admin'))
            ->getJson('/api/v1/templates/nonexistent_key');

        $this->assertApiError($response, 404, 'NOT_FOUND');
    }

    public function test_get_inactive_template_returns_conflict(): void
    {
        Template::factory()->create(['key' => 'inactive_tpl', 'is_active' => false]);

        $response = $this->withToken($this->makeToken('super_admin'))
            ->getJson('/api/v1/templates/inactive_tpl');

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'TEMPLATE_INACTIVE');
    }

    // ── List ────────────────────────────────────────────────────────────

    public function test_list_templates_returns_pagination_meta(): void
    {
        Template::factory()->count(3)->create();

        $response = $this->withToken($this->makeToken('super_admin'))
            ->getJson('/api/v1/templates');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('meta.pagination.total', 3);
    }

    public function test_list_filters_by_channel(): void
    {
        Template::factory()->create(['key' => 'email_tpl', 'channel' => 'email']);
        Template::factory()->create(['key' => 'push_tpl', 'channel' => 'push']);

        $response = $this->withToken($this->makeToken('super_admin'))
            ->getJson('/api/v1/templates?channel=email');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('meta.pagination.total', 1);
    }

    // ── Update ──────────────────────────────────────────────────────────

    public function test_update_template_bumps_version_on_content_change(): void
    {
        Template::factory()->create([
            'key'     => 'welcome_email',
            'body'    => 'Old body',
            'version' => 1,
        ]);

        $response = $this->withToken($this->makeToken('super_admin'))
            ->putJson('/api/v1/templates/welcome_email', [
                'body' => 'New body with {{ user_name }}',
            ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.version', 2);
        $this->assertDatabaseHas('templates', ['key' => 'welcome_email', 'version' => 2]);
    }

    public function test_update_template_does_not_bump_version_on_metadata_change(): void
    {
        Template::factory()->create([
            'key'     => 'welcome_email',
            'name'    => 'Old Name',
            'body'    => 'Same body',
            'version' => 1,
        ]);

        $response = $this->withToken($this->makeToken('super_admin'))
            ->putJson('/api/v1/templates/welcome_email', [
                'name' => 'New Name',
            ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.version', 1);
        $response->assertJsonPath('data.name', 'New Name');
    }

    // ── Delete ──────────────────────────────────────────────────────────

    public function test_delete_template_soft_deletes(): void
    {
        Template::factory()->create(['key' => 'to_delete']);

        $response = $this->withToken($this->makeToken('super_admin'))
            ->deleteJson('/api/v1/templates/to_delete');

        $this->assertApiSuccess($response);
        $this->assertSoftDeleted('templates', ['key' => 'to_delete']);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $response = $this->withToken($this->makeToken('super_admin'))
            ->deleteJson('/api/v1/templates/nonexistent');

        $this->assertApiError($response, 404, 'NOT_FOUND');
    }

    // ── Render ──────────────────────────────────────────────────────────

    public function test_render_template_success(): void
    {
        Template::factory()->create([
            'key'  => 'welcome_email',
            'body' => 'Hi {{ user_name }}, your OTP is {{ otp }}',
            'variables_schema' => [
                'required' => ['user_name', 'otp'],
            ],
        ]);

        $response = $this->withToken($this->makeToken('admin'))
            ->postJson('/api/v1/templates/welcome_email/render', [
                'variables' => ['user_name' => 'Mohammad', 'otp' => '1234'],
            ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.body_rendered', 'Hi Mohammad, your OTP is 1234');
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

        $response = $this->withToken($this->makeToken('admin'))
            ->postJson('/api/v1/templates/welcome_email/render', [
                'variables' => ['user_name' => 'Mohammad'],
            ]);

        $this->assertApiError($response, 422, 'VALIDATION_ERROR');
    }

    // ── Response format ─────────────────────────────────────────────────

    public function test_response_does_not_expose_numeric_id(): void
    {
        Template::factory()->create(['key' => 'test_tpl']);

        $response = $this->withToken($this->makeToken('super_admin'))
            ->getJson('/api/v1/templates/test_tpl');

        $response->assertOk();
        $this->assertArrayNotHasKey('id', $response->json('data'));
    }
}
