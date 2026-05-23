<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use LaravelApiBlueprint\Services\RouteParser;
use LaravelApiBlueprint\Services\OpenApiSpecGenerator;

class ApiBlueprintController
{
    public function ui(): View
    {
        return view('api-blueprint::docs', [
            'schemaUrl' => url(config('api-blueprint.path') . '/schema.json')
        ]);
    }

    public function schema(RouteParser $parser, OpenApiSpecGenerator $specGen): JsonResponse
    {
        $cachePath = storage_path('framework/cache/api-blueprint-spec.json');

        if (config('api-blueprint.cache_enabled') && file_exists($cachePath)) {
            $content = json_decode(file_get_contents($cachePath), true);
            if (is_array($content)) {
                return response()->json($content);
            }
        }

        $routes = $parser->getApiRoutes();
        $spec = $specGen->generate($routes);

        if (config('api-blueprint.cache_enabled')) {
            if (!is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }
            file_put_contents($cachePath, json_encode($spec, JSON_PRETTY_PRINT));
        }

        return response()->json($spec);
    }
}
