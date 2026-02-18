<?php

namespace App\Services\Implementations;

use App\Models\Template;
use App\Services\Contracts\TemplateServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class TemplateService implements TemplateServiceInterface
{
    public function createTemplate(array $data): Template
    {
        /** @var Template $template */
        $template = Template::create($data);

        Log::info('template.created', [
            'template_key' => $template->key,
            'version'      => $template->version,
        ]);

        return $template;
    }

    public function updateTemplate(Template $template, array $data): Template
    {
        $originalSubject = $template->subject;
        $originalBody    = $template->body;

        $template->fill($data);

        if ($this->contentChanged($template, $originalSubject, $originalBody)) {
            $template->version = ($template->version ?? 1) + 1;
        }

        $template->save();

        Log::info('template.updated', [
            'template_key' => $template->key,
            'version'      => $template->version,
        ]);

        return $template;
    }

    public function deleteTemplate(Template $template): void
    {
        $template->delete();

        Log::info('template.deleted', [
            'template_key' => $template->key,
            'version'      => $template->version,
        ]);
    }

    public function getByKey(string $key, bool $includeInactive = false): Template
    {
        $query = Template::query()->where('key', $key);

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query->firstOrFail();
    }

    public function paginateTemplates(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Template::query();

        if (! empty($filters['key'])) {
            $query->where('key', 'like', '%'.$filters['key'].'%');
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    private function contentChanged(Template $template, ?string $originalSubject, ?string $originalBody): bool
    {
        return $template->subject !== $originalSubject || $template->body !== $originalBody;
    }
}
