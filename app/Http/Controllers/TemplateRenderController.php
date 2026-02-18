<?php

namespace App\Http\Controllers;

use App\Exceptions\TemplateInactiveException;
use App\Http\Requests\RenderTemplateRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Contracts\TemplateRenderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TemplateRenderController extends Controller
{
    public function __construct(
        private readonly TemplateRenderServiceInterface $renderer,
    ) {}

    public function __invoke(RenderTemplateRequest $request, string $key): JsonResponse
    {
        try {
            $result = $this->renderer->render($key, $request->validated('variables'));

            return ApiResponse::success($result, 'Template rendered.');
        } catch (TemplateInactiveException $e) {
            return ApiResponse::conflict($e->getMessage(), 'TEMPLATE_INACTIVE');
        } catch (ValidationException $e) {
            return ApiResponse::validation($e->errors(), $e->getMessage());
        }
    }
}
