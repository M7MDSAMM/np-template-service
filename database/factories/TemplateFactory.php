<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        $key = Str::slug($this->faker->unique()->words(2, true), '_');

        return [
            'uuid'             => (string) Str::uuid(),
            'key'              => $key,
            'name'             => $this->faker->sentence(3),
            'channel'          => 'email',
            'subject'          => 'Hello {{ user_name }}',
            'body'             => 'Hi {{ user_name }}, welcome!',
            'variables_schema' => [
                'required' => ['user_name'],
            ],
            'version'          => 1,
            'is_active'        => true,
        ];
    }
}
