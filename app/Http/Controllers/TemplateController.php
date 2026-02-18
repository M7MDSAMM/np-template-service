<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Template;
use App\Services\Contracts\TemplateServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateServiceInterface $templates,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'key'       => $request->query('key'),
            'channel'   => $request->query('channel'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
        ];

        $paginator = $this->templates->paginateTemplates($filters, 15);

        return ApiResponse::list(
            $paginator->getCollection()->values()->toArray(),
            '',
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ]
        );
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = $this->templates->createTemplate($request->validated());

        return ApiResponse::created($template->toArray(), 'Template created.');
    }

    public function show(Template $template): JsonResponse
    {
        if (! $template->is_active) {
            return ApiResponse::conflict('Template is inactive.', 'TEMPLATE_INACTIVE');
        }

        return ApiResponse::success($template->toArray(), 'Template retrieved.');
    }

    public function update(UpdateTemplateRequest $request, Template $template): JsonResponse
    {
        $template = $this->templates->updateTemplate($template, $request->validated());

        return ApiResponse::success($template->toArray(), 'Template updated.');
    }

    public function destroy(Template $template): JsonResponse
    {
        $this->templates->deleteTemplate($template);

        return ApiResponse::success(null, 'Template deleted.');
    }
}
