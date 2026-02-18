<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_seeder_creates_ten_templates(): void
    {
        $this->seed(\Database\Seeders\TemplateSeeder::class);

        $this->assertSame(10, Template::count());
        $this->assertSame(10, Template::distinct('key')->count('key'));

        $this->assertTrue(
            Template::where('channel', 'email')->whereNotNull('subject')->exists(),
            'At least one email template should have a subject.'
        );
    }
}
