<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

class OpenApiSpecGenerator
{
    public function generate(array $apiRoutes): array
    {
        $overviewPath = config('api-blueprint.overview_path');
        $overviewContent = '';
        if ($overviewPath && file_exists($overviewPath)) {
            $overviewContent = file_get_contents($overviewPath);
        } else {
            $overviewContent = $this->getDefaultOverviewDescription();
        }

        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('app.name', 'Laravel') . ' API Specifications',
                'version' => config('api-blueprint.version', '1.0.0'),
                'description' => $overviewContent,
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
                        if (!empty($res['schema_properties'])) {
                            $resProperties = [];
                            foreach ($res['schema_properties'] as $key => $prop) {
                                $resProperties[$key] = [
                                    'type' => $prop['type']
                                ];
                                if ($prop['type'] === 'string') {
                                    if ($key === 'token') {
                                        $resProperties[$key]['example'] = 'd3b07384d113edec49eaa6238ad5ff00';
                                    } elseif ($key === 'message') {
                                        $resProperties[$key]['example'] = 'Operation completed successfully.';
                                    }
                                } elseif ($prop['type'] === 'object') {
                                    $resProperties[$key]['properties'] = new \stdClass();
                                } elseif ($prop['type'] === 'array') {
                                    $resProperties[$key]['items'] = ['type' => 'object'];
                                }
                            }
                            $responses[$code] = [
                                'description' => $res['description'] ?? 'Successful operation',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => $resProperties
                                        ]
                                    ]
                                ]
                            ];
                        } else {
                            $responses[$code] = $res;
                        }
                    }
                }

                // If GET or DELETE, map nested rules to query parameters
                if (!empty($route['nested_rules']) && in_array($method, ['get', 'delete'])) {
                    $queryParameters = $this->mapNestedRulesToQueryParameters($route['nested_rules']);
                    $parameters = array_merge($parameters, $queryParameters);
                }

                $pathItem = [
                    'summary' => $route['summary'] ?? $route['name'],
                    'tags' => $route['tags'] ?? $this->determineRouteTags($route['uri']),
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

        // Generate high-level x-tagGroups to create collapsible parent version folders in Stoplight Elements
        $allTags = [];
        foreach ($apiRoutes as $route) {
            $tags = $route['tags'] ?? $this->determineRouteTags($route['uri']);
            foreach ($tags as $tag) {
                $allTags[$tag] = true;
            }
        }
        $allTags = array_keys($allTags);

        $tagGroups = [];
        $generalTags = [];
        foreach ($allTags as $tag) {
            $tagStr = is_array($tag) ? implode(', ', $tag) : (string) $tag;
            $matches = [];
            if (preg_match('/^(v[0-9]+)\s*[\/\-]\s*(.+)$/i', $tagStr, $matches)) {
                $versionGroup = strtoupper((string) $matches[1]); // e.g. V1
                $tagGroups[$versionGroup][] = $tagStr;
            } else {
                $generalTags[] = $tagStr;
            }
        }

        $xTagGroups = [];
        foreach ($tagGroups as $groupName => $tags) {
            $xTagGroups[] = [
                'name' => $groupName,
                'tags' => $tags,
            ];
        }

        if (!empty($generalTags)) {
            $xTagGroups[] = [
                'name' => 'General API',
                'tags' => $generalTags,
            ];
        }

        if (!empty($xTagGroups)) {
            $spec['x-tagGroups'] = $xTagGroups;
        }

        return $spec;
    }

    /**
     * Maps nested rule trees to flat query parameters for GET and DELETE endpoints.
     */
    protected function mapNestedRulesToQueryParameters(array $properties): array
    {
        $params = [];
        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';
            $required = $prop['required'] ?? false;

            if ($type === 'object') {
                $subParams = $this->mapNestedRulesToQueryParameters($prop['properties'] ?? []);
                foreach ($subParams as $subParam) {
                    $subParam['name'] = $name . '[' . $subParam['name'] . ']';
                    $params[] = $subParam;
                }
            } elseif ($type === 'array') {
                $params[] = [
                    'name' => $name,
                    'in' => 'query',
                    'required' => $required,
                    'schema' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ];
            } else {
                $param = [
                    'name' => $name,
                    'in' => 'query',
                    'required' => $required,
                    'schema' => [
                        'type' => ($type === 'number') ? 'number' : (($type === 'boolean') ? 'boolean' : 'string'),
                    ],
                ];

                if (!empty($prop['rules'])) {
                    $rules = $prop['rules'];
                    $rulesString = implode('|', $rules);

                    if (str_contains($rulesString, 'email')) {
                        $param['schema']['format'] = 'email';
                    } elseif (str_contains($rulesString, 'url')) {
                        $param['schema']['format'] = 'uri';
                    } elseif (str_contains($rulesString, 'uuid')) {
                        $param['schema']['format'] = 'uuid';
                    } elseif (str_contains($rulesString, 'date')) {
                        $param['schema']['format'] = 'date';
                    }

                    // Parse nullable and enum
                    foreach ($rules as $rule) {
                        if ($rule === 'nullable') {
                            $param['schema']['nullable'] = true;
                        } elseif (str_starts_with($rule, 'in:')) {
                            $enumVals = array_map(fn($v) => trim($v, "'\" "), explode(',', substr($rule, 3)));
                            $param['schema']['enum'] = $enumVals;
                        }
                    }
                }

                $params[] = $param;
            }
        }
        return $params;
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
                        } elseif ($rule === 'nullable') {
                            $mapped[$name]['nullable'] = true;
                        } elseif (str_starts_with($rule, 'in:')) {
                            $enumVals = array_map(fn($v) => trim($v, "'\" "), explode(',', substr($rule, 3)));
                            $mapped[$name]['enum'] = $enumVals;
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

    /**
     * Get the default premium Markdown integration guide.
     */
    protected function getDefaultOverviewDescription(): string
    {
        return "# API Documentation & Integration Guide\n\n" .
            "Welcome to the official API documentation! This interactive specifications dashboard is designed to provide you with all the details required to integrate with our system seamlessly.\n\n" .
            "## Getting Started\n\n" .
            "To authenticate your API requests:\n" .
            "1. Obtain an API bearer token from your account dashboard.\n" .
            "2. In this dashboard, click the **Authorize** button at the top-right of an endpoint or in the sidebar.\n" .
            "3. Paste your token (format: `Bearer <token>`).\n\n" .
            "## Client Code Generation\n\n" .
            "We provide dynamic type-safe client schemas generated in real-time from active request rules:\n" .
            "*   Click on **Client Schemas** in the top navigation bar.\n" .
            "*   Select your target language (TypeScript, Swift, Java, Dart, Go).\n" .
            "*   Copy or download the payload models to jumpstart your development.\n\n" .
            "## Standard Responses\n\n" .
            "Unless stated otherwise, our API endpoints communicate using the standard JSON format:\n" .
            "*   `200 OK` - Operation completed successfully.\n" .
            "*   `201 Created` - Resource created successfully.\n" .
            "*   `400 Bad Request` - Invalid request syntax or structure.\n" .
            "*   `401 Unauthenticated` - Authentication failed or bearer token missing.\n" .
            "*   `403 Forbidden` - Insufficient privileges to access the resource.\n" .
            "*   `422 Unprocessable Content` - Request validation failed (details provided in response body).";
    }
}
