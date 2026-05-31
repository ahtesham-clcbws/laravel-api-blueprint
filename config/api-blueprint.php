<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Global Enable/Disable Toggle
    |--------------------------------------------------------------------------
    */
    'enabled' => env('API_BLUEPRINT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Routing and Endpoint Options
    |--------------------------------------------------------------------------
    */
    'path' => env('API_BLUEPRINT_PATH', 'api-blueprint'),

    /*
    |--------------------------------------------------------------------------
    | Route Middlewares
    |--------------------------------------------------------------------------
    | Define the middleware stack applied to the documentation views and schema.
    | Add custom middleware or session/authentication guards here.
    */
    'middleware' => [
        LaravelApiBlueprint\Http\Middleware\GatedDocAccess::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Check
    |--------------------------------------------------------------------------
    | Allows restricting access using custom gates, roles or logic.
    | When set to a class and method, it will be executed to verify access.
    */
    'auth' => [
        // Enforce Basic Auth credentials as a quick gate
        'username' => env('API_BLUEPRINT_USERNAME', 'admin'),
        'password' => env('API_BLUEPRINT_PASSWORD', 'secret'),

        // Optional custom Gate authorization (e.g. 'view-api-blueprint')
        'gate' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Filters
    |--------------------------------------------------------------------------
    | Define which routes are inspected. Routes must match these prefixes.
    */
    'route_prefixes' => ['api/'],

    /*
    |--------------------------------------------------------------------------
    | Schema Export Cache
    |--------------------------------------------------------------------------
    | If enabled, parsed OpenAPI specs are cached in production environments
    | to prevent CPU-intensive reflection scans on every page view.
    */
    'cache_enabled' => env('API_BLUEPRINT_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Build Output Configuration
    |--------------------------------------------------------------------------
    */
    'outputs' => [
        'postman_path'    => storage_path('app/api-blueprint/postman_collection.json'),
        'typescript_path' => storage_path('app/api-blueprint/api.d.ts'),
        'swift_path'      => storage_path('app/api-blueprint/api.swift'),
        'java_path'       => storage_path('app/api-blueprint/dto'), // Directory for class files
        'dart_path'       => storage_path('app/api-blueprint/api.dart'),
        'go_path'         => storage_path('app/api-blueprint/api.go'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Grouping / Tagging Mappings
    |--------------------------------------------------------------------------
    | Define custom tags/groups for specific URI patterns or controller names.
    | Matches can use wildcards (e.g. 'api/v1/auth/*' or 'App\Http\Controllers\Admin\*').
    */
    'groups' => [
        // 'api/v1/auth/*' => 'Authentication',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Documentation Overview
    |--------------------------------------------------------------------------
    | Path to a Markdown file containing the custom overview/guide documentation.
    | If not specified or if the file does not exist, a default elegant guide is shown.
    */
    'overview_path' => resource_path('api-blueprint/overview.md'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    | The current version of your API endpoints.
    | This is mapped to the OpenAPI and Postman schemas automatically.
    */
    'version' => env('API_BLUEPRINT_VERSION', '1.0.0'),
];
