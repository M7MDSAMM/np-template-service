<?php

namespace App\Services\Contracts;

interface TemplateRenderServiceInterface
{
    /**
     * @return array{key:string,channel:string,version:int,subject_rendered:?string,body_rendered:string}
     */
    public function render(string $key, array $variables): array;
}
