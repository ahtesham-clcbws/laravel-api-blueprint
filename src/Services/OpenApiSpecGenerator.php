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

                $parameters = [
                    [
                        'name' => 'Accept',
                        'in' => 'header',
                        'required' => true,
                        'schema' => ['type' => 'string', 'default' => 'application/json'],
                    ],
                ];

                // Automatically extract and document path parameters (e.g. {product} or {id})
                if (preg_match_all('/\{([^}]+)\}/', $uri, $matches)) {
                    foreach ($matches[1] as $paramName) {
                        $paramName = rtrim($paramName, '?');
                        $parameters[] = [
                            'name' => $paramName,
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'string'],
                            'description' => 'The ' . $paramName . ' identifier.',
                        ];
                    }
                }

                $responses = [
                    '200' => ['description' => 'Successful operation'],
                    '201' => ['description' => 'Resource created successfully'],
                    '401' => ['description' => 'Unauthenticated'],
                    '422' => ['description' => 'Validation error'],
                ];

                if (!empty($route['responses'])) {
                    foreach ($route['responses'] as $code => $res) {
                        $responses[$code] = $res;
                    }
                }

                $pathItem = [
                    'summary' => $route['summary'] ?? $route['name'],
                    'tags' => $this->determineRouteTags($route['uri']),
                    'parameters' => $parameters,
                    'responses' => $responses,
                ];

                if (!empty($route['description'])) {
                    $pathItem['description'] = $route['description'];
                }

                // If authentication is required, map the Bearer Token security requirements
                if ($route['auth_required'] ?? false) {
                    $pathItem['security'] = [
                        [
                            'bearerAuth' => [],
                        ],
                    ];
                }

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

                if (!empty($prop['rules'])) {
                    $rules = $prop['rules'];
                    $rulesString = implode('|', $rules);

                    if (str_contains($rulesString, 'email') || $name === 'email') {
                        $mapped[$name]['format'] = 'email';
                        $mapped[$name]['example'] = 'user@example.com';
                    } elseif (str_contains($rulesString, 'url')) {
                        $mapped[$name]['format'] = 'uri';
                        $mapped[$name]['example'] = 'https://example.com';
                    } elseif (str_contains($rulesString, 'uuid')) {
                        $mapped[$name]['format'] = 'uuid';
                        $mapped[$name]['example'] = '123e4567-e89b-12d3-a456-426614174000';
                    } elseif (str_contains($rulesString, 'date')) {
                        $mapped[$name]['format'] = 'date';
                        $mapped[$name]['example'] = date('Y-m-d');
                    } elseif (str_contains($rulesString, 'password') || str_contains($name, 'password')) {
                        $mapped[$name]['format'] = 'password';
                        $mapped[$name]['example'] = 'Password123!';
                    } elseif (str_contains($name, 'name')) {
                        $mapped[$name]['example'] = 'John Doe';
                    }

                    // Parse min/max rules
                    foreach ($rules as $rule) {
                        if (str_starts_with($rule, 'min:')) {
                            $minVal = (int) substr($rule, 4);
                            if ($type === 'number') {
                                $mapped[$name]['minimum'] = $minVal;
                            } else {
                                $mapped[$name]['minLength'] = $minVal;
                            }
                        } elseif (str_starts_with($rule, 'max:')) {
                            $maxVal = (int) substr($rule, 4);
                            if ($type === 'number') {
                                $mapped[$name]['maximum'] = $maxVal;
                            } else {
                                $mapped[$name]['maxLength'] = $maxVal;
                            }
                        }
                    }
                } else {
                    if ($name === 'email') {
                        $mapped[$name]['format'] = 'email';
                        $mapped[$name]['example'] = 'user@example.com';
                    } elseif (str_contains($name, 'password')) {
                        $mapped[$name]['format'] = 'password';
                        $mapped[$name]['example'] = 'Password123!';
                    } elseif (str_contains($name, 'name')) {
                        $mapped[$name]['example'] = 'John Doe';
                    }
                }
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
