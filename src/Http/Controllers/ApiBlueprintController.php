<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use LaravelApiBlueprint\Services\RouteParser;
use LaravelApiBlueprint\Services\OpenApiSpecGenerator;
use LaravelApiBlueprint\Services\PostmanGenerator;

class ApiBlueprintController
{
    public function ui(RouteParser $parser): View
    {
        $routes = $parser->getApiRoutes();
        $versions = [];
        foreach ($routes as $route) {
            $clean = ltrim($route['uri'], '/');
            $segments = explode('/', $clean);
            foreach ($segments as $seg) {
                if (preg_match('/^v[0-9]+$/i', $seg)) {
                    $versions[] = strtolower($seg);
                }
            }
        }
        $versions = array_unique($versions);
        sort($versions);

        return view('api-blueprint::docs', [
            'schemaUrl' => url(config('api-blueprint.path') . '/schema.json'),
            'postmanUrl' => url(config('api-blueprint.path') . '/postman.json'),
            'versions' => $versions
        ]);
    }

    public function schema(RouteParser $parser, OpenApiSpecGenerator $specGen): JsonResponse
    {
        $version = request('version');

        // Bypass cache if filtering by version dynamically
        $cachePath = storage_path('framework/cache/api-blueprint-spec.json');
        if (empty($version) && config('api-blueprint.cache_enabled') && file_exists($cachePath)) {
            $content = json_decode(file_get_contents($cachePath), true);
            if (is_array($content)) {
                return response()->json($content);
            }
        }

        $routes = $parser->getApiRoutes();

        // 1. Filter routes by version if requested
        if ($version) {
            $routes = array_filter($routes, function ($route) use ($version) {
                $clean = ltrim($route['uri'], '/');
                $segments = explode('/', $clean);
                foreach ($segments as $seg) {
                    if (strtolower($seg) === strtolower($version)) {
                        return true;
                    }
                }
                return false;
            });

            // 2. Strip the prepended "V1 / " tag prefixes from routes inside this version scope
            $routes = array_map(function ($route) use ($version) {
                $prefix = strtoupper($version) . ' / ';
                $route['tags'] = array_map(function ($tag) use ($prefix) {
                    if (str_starts_with($tag, $prefix)) {
                        return substr($tag, strlen($prefix));
                    }
                    return $tag;
                }, $route['tags'] ?? []);
                return $route;
            }, $routes);
        }

        $spec = $specGen->generate($routes);

        if (empty($version) && config('api-blueprint.cache_enabled')) {
            if (!is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }
            file_put_contents($cachePath, json_encode($spec, JSON_PRETTY_PRINT));
        }

        return response()->json($spec);
    }

    public function postman(RouteParser $parser, PostmanGenerator $postmanGen): JsonResponse
    {
        $routes = $parser->getApiRoutes();
        $version = request('version');

        // Filter and strip tag prefixes in Postman dynamically if version requested
        if ($version) {
            $routes = array_filter($routes, function ($route) use ($version) {
                $clean = ltrim($route['uri'], '/');
                $segments = explode('/', $clean);
                foreach ($segments as $seg) {
                    if (strtolower($seg) === strtolower($version)) {
                        return true;
                    }
                }
                return false;
            });

            $routes = array_map(function ($route) use ($version) {
                $prefix = strtoupper($version) . ' / ';
                $route['tags'] = array_map(function ($tag) use ($prefix) {
                    if (str_starts_with($tag, $prefix)) {
                        return substr($tag, strlen($prefix));
                    }
                    return $tag;
                }, $route['tags'] ?? []);
                return $route;
            }, $routes);
        }

        $collection = json_decode($postmanGen->generate($routes), true);

        return response()->json($collection, 200, [
            'Content-Disposition' => 'attachment; filename="postman_collection.json"'
        ]);
    }
}
