<?php

namespace App\Services\Implementations;

use App\Exceptions\TemplateInactiveException;
use App\Models\Template;
use App\Services\Contracts\TemplateRenderServiceInterface;
use App\Services\Contracts\TemplateServiceInterface;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TemplateRenderService implements TemplateRenderServiceInterface
{
    public function __construct(
        private readonly TemplateServiceInterface $templates,
    ) {}

    public function render(string $key, array $variables): array
    {
        $template = $this->templates->getByKey($key, includeInactive: true);

        if (! $template->is_active) {
            throw new TemplateInactiveException('Template is inactive.');
        }

        $this->validateVariables($template, $variables);

        $subjectRendered = $template->subject
            ? Blade::render($this->normalizePlaceholders($template->subject), $variables)
            : null;

        $bodyRendered = Blade::render($this->normalizePlaceholders($template->body), $variables);

        Log::info('template.rendered', [
            'template_key' => $template->key,
            'version'      => $template->version,
        ]);

        return [
            'key'              => $template->key,
            'channel'          => $template->channel,
            'version'          => $template->version,
            'subject_rendered' => $subjectRendered,
            'body_rendered'    => $bodyRendered,
        ];
    }

    private function validateVariables(Template $template, array $variables): void
    {
        $schema = $template->variables_schema ?? [];
        $required = $schema['required'] ?? [];
        $optional = $schema['optional'] ?? [];
        $rules    = $schema['rules'] ?? [];

        $allowedKeys = array_unique(array_merge($required, $optional));
        if ($allowedKeys) {
            $unexpected = array_diff(array_keys($variables), $allowedKeys);

            if ($unexpected) {
                throw ValidationException::withMessages([
                    'variables' => ['Unexpected variables: '.implode(', ', $unexpected)],
                ]);
            }
        }

        $validationRules = [];

        foreach ($required as $name) {
            $validationRules["variables.$name"] = array_merge(['required'], $this->parseRule($rules[$name] ?? null));
        }

        foreach ($optional as $name) {
            $validationRules["variables.$name"] = array_merge(['sometimes'], $this->parseRule($rules[$name] ?? null));
        }

        if ($validationRules) {
            Validator::make(['variables' => $variables], $validationRules)->validate();
        }
    }

    private function parseRule(?string $rule): array
    {
        if (! $rule) {
            return [];
        }

        return is_string($rule) ? explode('|', $rule) : [];
    }

    private function normalizePlaceholders(string $content): string
    {
        return preg_replace_callback('/{{\\s*(\\w+)\\s*}}/', function (array $matches) {
            return '{{ $'.$matches[1].' }}';
        }, $content);
    }
}
