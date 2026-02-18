<?php

namespace App\Providers;

use App\Services\Contracts\TemplateRenderServiceInterface;
use App\Services\Contracts\TemplateServiceInterface;
use App\Services\Implementations\TemplateRenderService;
use App\Services\Implementations\TemplateService;
use App\Domain\Auth\JwtTokenServiceInterface;
use App\Infrastructure\Auth\Rs256JwtTokenService;
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
        $this->app->bind(JwtTokenServiceInterface::class, Rs256JwtTokenService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
