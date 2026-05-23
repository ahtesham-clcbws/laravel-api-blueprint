<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

class OpenApiSpecGenerator
{
    public function generate(array $apiRoutes): array
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('app.name', 'Laravel') . ' API Specifications',
                'version' => '1.0.0',
                'description' => 'Automatically generated API Specifications via Laravel API Blueprint.',
            ],
            'servers' => [
                [
                    'url' => rtrim(config('app.url', 'http://localhost'), '/'),
                    'description' => config('app.name', 'Laravel') . ' API Server',
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'token',
                    ],
                ],
            ],
            'paths' => [],
        ];

        foreach ($apiRoutes as $route) {
            $uri = '/' . ltrim($route['uri'], '/');
            foreach ($route['methods'] as $method) {
                $method = strtolower($method);

                $pathItem = [
                    'summary' => $route['name'],
                    'tags' => $this->determineRouteTags($route['uri']),
                    // Accept: application/json forces Laravel to return JSON
                    // instead of a 302 HTML redirect on validation failure.
                    'parameters' => [
                        [
                            'name' => 'Accept',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string', 'default' => 'application/json'],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Successful operation'],
                        '201' => ['description' => 'Resource created successfully'],
                        '401' => ['description' => 'Unauthenticated'],
                        '422' => ['description' => 'Validation error'],
                    ],
                ];

                // Parse request body for POST/PUT/PATCH methods
                if (!empty($route['nested_rules']) && in_array($method, ['post', 'put', 'patch'])) {
                    $properties = $this->mapNestedPropertiesToOpenApi($route['nested_rules']);
                    $requiredFields = $this->getRequiredFields($route['nested_rules']);

                    $schema = [
                        'type' => 'object',
                        'properties' => $properties,
                    ];

                    if (!empty($requiredFields)) {
                        $schema['required'] = $requiredFields;
                    }

                    $pathItem['requestBody'] = [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => $schema,
                            ],
                        ],
                    ];
                }

                $spec['paths'][$uri][$method] = $pathItem;
            }
        }

        return $spec;
    }

    protected function mapNestedPropertiesToOpenApi(array $properties): array
    {
        $mapped = [];

        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';

            if ($type === 'object') {
                $subProperties = $this->mapNestedPropertiesToOpenApi($prop['properties'] ?? []);
                $required = $this->getRequiredFields($prop['properties'] ?? []);

                $mapped[$name] = [
                    'type' => 'object',
                    'properties' => $subProperties,
                ];

                if (!empty($required)) {
                    $mapped[$name]['required'] = $required;
                }
            } elseif ($type === 'array') {
                if (isset($prop['items']) && !empty($prop['items']['properties'])) {
                    $subProperties = $this->mapNestedPropertiesToOpenApi($prop['items']['properties'] ?? []);
                    $required = $this->getRequiredFields($prop['items']['properties'] ?? []);

                    $itemSchema = [
                        'type' => 'object',
                        'properties' => $subProperties,
                    ];

                    if (!empty($required)) {
                        $itemSchema['required'] = $required;
                    }

                    $mapped[$name] = [
                        'type' => 'array',
                        'items' => $itemSchema,
                    ];
                } elseif (isset($prop['items'])) {
                    $itemType = $prop['items']['type'];
                    $mapped[$name] = [
                        'type' => 'array',
                        'items' => [
                            'type' => ($itemType === 'number') ? 'number' : (($itemType === 'boolean') ? 'boolean' : 'string'),
                        ],
                    ];
                } else {
                    $mapped[$name] = [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ];
                }
            } else {
                $mapped[$name] = [
                    'type' => ($type === 'number') ? 'number' : (($type === 'boolean') ? 'boolean' : 'string'),
                ];
            }
        }

        return $mapped;
    }

    protected function getRequiredFields(array $properties): array
    {
        $required = [];
        foreach ($properties as $name => $prop) {
            if ($prop['required'] ?? false) {
                $required[] = $name;
            }
        }
        return $required;
    }

    /**
     * Determine route tags based on the URI structure segment.
     */
    protected function determineRouteTags(string $uri): array
    {
        $clean = ltrim($uri, '/');
        if (str_starts_with($clean, 'api/')) {
            $clean = substr($clean, 4);
        }
        
        $segments = explode('/', $clean);
        $primary = $segments[0] ?? 'General';
        
        return [ucfirst($primary)];
    }
}
