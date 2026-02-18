<?php

namespace App\Services\Contracts;

use App\Models\Template;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TemplateServiceInterface
{
    public function createTemplate(array $data): Template;

    public function updateTemplate(Template $template, array $data): Template;

    public function deleteTemplate(Template $template): void;

    public function getByKey(string $key, bool $includeInactive = false): Template;

    public function paginateTemplates(array $filters, int $perPage = 15): LengthAwarePaginator;
}
