<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use LaravelApiBlueprint\Http\Controllers\ApiBlueprintController;
use LaravelApiBlueprint\Commands\ExportApiArtifacts;

class ApiBlueprintServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/api-blueprint.php', 'api-blueprint');
    }

    public function boot(): void
    {
        if (!config('api-blueprint.enabled')) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/api-blueprint.php' => config_path('api-blueprint.php'),
            ], 'api-blueprint-config');

            $this->commands([
                ExportApiArtifacts::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'api-blueprint');
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $path = config('api-blueprint.path', 'api-blueprint');
        $middleware = config('api-blueprint.middleware', []);

        Route::prefix($path)
            ->middleware($middleware)
            ->group(function () {
                Route::get('/', [ApiBlueprintController::class, 'ui'])->name('api-blueprint.ui');
                Route::get('/schema.json', [ApiBlueprintController::class, 'schema'])->name('api-blueprint.schema');
            });
    }
}
