<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateApiTest extends TestCase
{
    use RefreshDatabase;

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

        $response = $this->postJson('/api/v1/templates', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.key', 'welcome_email')
            ->assertJsonPath('meta', []);

        $this->assertDatabaseHas('templates', ['key' => 'welcome_email', 'version' => 1]);
    }

    public function test_get_template_by_key(): void
    {
        $template = Template::factory()->create(['key' => 'order_shipped']);

        $response = $this->getJson('/api/v1/templates/order_shipped');

        $response->assertOk()
            ->assertJsonPath('data.key', $template->key)
            ->assertJsonPath('meta', []);
    }

    public function test_list_templates_returns_pagination_meta(): void
    {
        Template::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/templates');

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

        $response = $this->postJson('/api/v1/templates/welcome_email/render', [
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

        $response = $this->postJson('/api/v1/templates/welcome_email/render', [
            'variables' => ['user_name' => 'Mohammad'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }
}
