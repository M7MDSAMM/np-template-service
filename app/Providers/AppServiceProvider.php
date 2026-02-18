<?php

namespace App\Providers;

use App\Services\Contracts\TemplateRenderServiceInterface;
use App\Services\Contracts\TemplateServiceInterface;
use App\Services\Implementations\TemplateRenderService;
use App\Services\Implementations\TemplateService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TemplateServiceInterface::class, TemplateService::class);
        $this->app->bind(TemplateRenderServiceInterface::class, TemplateRenderService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
