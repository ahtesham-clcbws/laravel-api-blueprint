<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

class PostmanGenerator
{
    public function generate(array $apiRoutes): string
    {
        $collection = [
            'info' => [
                'name'   => config('app.name', 'Laravel') . ' API Collection',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                'description' => 'Automatically generated Postman Collection via Laravel API Blueprint.',
            ],
            'item' => [],
        ];

        $groupedItems = [];

        foreach ($apiRoutes as $route) {
            $tag = $route['tags'][0] ?? 'General';

            foreach ($route['methods'] as $method) {
                // Convert `{param}` variables to `:param` in URL for Postman compatibility
                $rawUri = ltrim($route['uri'], '/');
                $postmanUri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', ':$1', $rawUri);
                
                // Collect URL variables
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $rawUri, $matches);
                $variables = [];
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $param) {
                        $variables[] = [
                            'key' => $param,
                            'value' => '1',
                            'description' => "Path parameter: {$param}"
                        ];
                    }
                }

                $bodyParams = [];
                if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($route['nested_rules'])) {
                    $jsonMock = $this->buildJsonMock($route['nested_rules']);
                    $bodyParams = [
                        'mode' => 'raw',
                        'raw'  => json_encode($jsonMock, JSON_PRETTY_PRINT),
                        'options' => [
                            'raw' => [
                                'language' => 'json'
                            ]
                        ]
                    ];
                }

                $request = [
                    'method' => $method,
                    'header' => [
                        [
                            'key' => 'Accept',
                            'value' => 'application/json',
                            'type' => 'text'
                        ],
                        [
                            'key' => 'Content-Type',
                            'value' => 'application/json',
                            'type' => 'text'
                        ]
                    ],
                    'body'   => $bodyParams,
                    'url'    => [
                        'raw'  => '{{base_url}}/' . $postmanUri,
                        'host' => ['{{base_url}}'],
                        'path' => explode('/', $postmanUri),
                    ],
                ];

                if (!empty($variables)) {
                    $request['url']['variable'] = $variables;
                }

                $groupedItems[$tag][] = [
                    'name'    => $route['name'],
                    'request' => $request,
                    'response' => []
                ];
            }
        }

        foreach ($groupedItems as $tag => $items) {
            $collection['item'][] = [
                'name' => $tag,
                'item' => $items
            ];
        }

        return json_encode($collection, JSON_PRETTY_PRINT);
    }

    /**
     * Recursively build a mock JSON data object matching the shape of the validation schema.
     */
    protected function buildJsonMock(array $properties): array
    {
        $mock = [];

        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';

            if ($type === 'number') {
                $mock[$name] = 0;
            } elseif ($type === 'boolean') {
                $mock[$name] = false;
            } elseif ($type === 'object') {
                $mock[$name] = $this->buildJsonMock($prop['properties'] ?? []);
            } elseif ($type === 'array') {
                if (isset($prop['items']) && $prop['items']['type'] === 'object') {
                    $mock[$name] = [$this->buildJsonMock($prop['items']['properties'] ?? [])];
                } elseif (isset($prop['items'])) {
                    $itemType = $prop['items']['type'];
                    $mockItem = ($itemType === 'number') ? 0 : (($itemType === 'boolean') ? false : "");
                    $mock[$name] = [$mockItem];
                } else {
                    $mock[$name] = [];
                }
            } else {
                $mock[$name] = "";
            }
        }

        return $mock;
    }
}
