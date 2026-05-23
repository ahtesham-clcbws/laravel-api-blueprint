<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Container\Container;
use ReflectionMethod;
use Throwable;

class RouteParser
{
    /**
     * Parse and retrieve all valid API routes with their corresponding validation structures.
     */
    public function getApiRoutes(): array
    {
        $apiRoutes = [];
        $routes = Route::getRoutes()->getRoutes();
        $prefixes = Config::get('api-blueprint.route_prefixes', ['api/']);

        foreach ($routes as $route) {
            // Match configured prefixes
            $matchesPrefix = false;
            foreach ($prefixes as $prefix) {
                if (str_starts_with(ltrim($route->uri(), '/'), ltrim($prefix, '/'))) {
                    $matchesPrefix = true;
                    break;
                }
            }

            if (!$matchesPrefix) {
                continue;
            }

            $action = $route->getAction('uses');
            if (!is_string($action) || !str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action);
            if (!class_exists($controller) || !method_exists($controller, $method)) {
                continue;
            }

            $rawRules = $this->extractValidationRules($controller, $method, $route);

            // Automatically inject confirmation fields for any 'confirmed' validation rule
            $injectedRules = $rawRules;
            foreach ($rawRules as $field => $rules) {
                $rulesString = is_array($rules) ? implode('|', $rules) : (string)$rules;
                if (str_contains($rulesString, 'confirmed')) {
                    $confField = $field . '_confirmation';
                    if (!isset($rawRules[$confField])) {
                        $confRules = [];
                        if (str_contains($rulesString, 'required')) {
                            $confRules[] = 'required';
                        }
                        if (str_contains($rulesString, 'string')) {
                            $confRules[] = 'string';
                        }
                        if (empty($confRules)) {
                            $confRules[] = 'string';
                        }
                        $injectedRules[$confField] = $confRules;
                    }
                }
            }
            $rawRules = $injectedRules;

            $nestedSchema = $this->buildNestedSchema($rawRules);

            // Reflection-based PHPDoc extraction (Scramble-equivalent automatic parsing)
            $summary = '';
            $description = '';
            $customResponses = [];
            try {
                $reflection = new \ReflectionMethod($controller, $method);
                $docComment = $reflection->getDocComment();
                if ($docComment !== false) {
                    $lines = explode("\n", $docComment);
                    $cleanLines = [];
                    foreach ($lines as $line) {
                        $line = trim($line, "/* \t\r\n");
                        if ($line === '') {
                            continue;
                        }
                        if (str_starts_with($line, '@response ')) {
                            $parts = preg_split('/\s+/', substr($line, 10), 2);
                            if (count($parts) >= 1) {
                                $code = $parts[0];
                                $desc = $parts[1] ?? 'Successful operation';
                                $customResponses[$code] = ['description' => $desc];
                            }
                        } elseif (!str_starts_with($line, '@')) {
                            $cleanLines[] = $line;
                        }
                    }
                    if (count($cleanLines) > 0) {
                        $summary = $cleanLines[0];
                        if (count($cleanLines) > 1) {
                            $description = implode(' ', array_slice($cleanLines, 1));
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fail-safe
            }

            $apiRoutes[] = [
                'uri'          => $route->uri(),
                'methods'      => array_filter($route->methods(), fn($m) => $m !== 'HEAD'),
                'name'         => $route->getName() ?? $this->generateRouteName($route->uri(), $route->methods()),
                'summary'      => $summary !== '' ? $summary : ($route->getName() ?? $this->generateRouteName($route->uri(), $route->methods())),
                'description'  => $description,
                'responses'    => $customResponses,
                'raw_rules'    => $rawRules,
                'nested_rules' => $nestedSchema,
            ];
        }

        return $apiRoutes;
    }

    /**
     * Safe extraction of FormRequest validation rules using IoC container resolution
     * and request replication to prevent runtime execution failures.
     */
    protected function extractValidationRules(string $controller, string $method, LaravelRoute $route): array
    {
        try {
            $reflection = new ReflectionMethod($controller, $method);
            foreach ($reflection->getParameters() as $param) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $className = $type->getName();
                    if (is_subclass_of($className, FormRequest::class)) {
                        return $this->resolveRequestRulesInContext($className, $route);
                    }
                }
            }
        } catch (Throwable $e) {
            // Fail gracefully to prevent interrupting the route parser execution
        }

        return [];
    }

    /**
     * Resolves the FormRequest from container while mocking current Request parameters to avoid crashes.
     */
    protected function resolveRequestRulesInContext(string $className, LaravelRoute $route): array
    {
        $originalRequest = App::make('request');
        
        try {
            // Mock a basic request corresponding to this route context
            $methods = $route->methods();
            $method = count($methods) > 0 ? $methods[0] : 'GET';
            
            $dummyRequest = Request::create($route->uri(), $method);
            
            // Replicate route parameter bindings
            if (method_exists($route, 'parameters')) {
                $dummyRequest->setRouteResolver(fn() => $route);
            }

            // Bind temporary mock to the container
            App::instance('request', $dummyRequest);

            // Manually instantiate to bypass container validation resolution hooks
            $requestInstance = new $className();
            
            // Set container manually so dependency lookup works
            if (method_exists($requestInstance, 'setContainer')) {
                $requestInstance->setContainer(Container::getInstance());
            }

            // Set redirector if needed
            if (method_exists($requestInstance, 'setRedirector') && App::bound(Redirector::class)) {
                $requestInstance->setRedirector(App::make(Redirector::class));
            }
            
            $rules = method_exists($requestInstance, 'rules') ? $requestInstance->rules() : [];
            
            // Normalize rules array
            $normalized = [];
            foreach ($rules as $field => $ruleset) {
                $normalized[$field] = $this->normalizeRuleset($ruleset);
            }
            return $normalized;

        } catch (Throwable $e) {
            return [];
        } finally {
            // Always restore the original request context
            App::instance('request', $originalRequest);
        }
    }

    /**
     * Clean and normalize validation rules into string representations.
     */
    protected function normalizeRuleset(mixed $ruleset): array
    {
        if (is_string($ruleset)) {
            return explode('|', $ruleset);
        }

        if (!is_array($ruleset)) {
            return [];
        }

        $normalized = [];
        foreach ($ruleset as $rule) {
            if (is_string($rule)) {
                $normalized[] = $rule;
            } elseif (is_object($rule)) {
                if (method_exists($rule, '__toString')) {
                    $normalized[] = (string) $rule;
                } else {
                    $className = get_class($rule);
                    $pos = strrpos($className, '\\');
                    $baseName = $pos === false ? $className : substr($className, $pos + 1);
                    $normalized[] = strtolower(str_replace('Rule', '', $baseName));
                }
            }
        }

        return $normalized;
    }

    /**
     * Build a nested hierarchical schema tree from flat dot-notation validation rules.
     */
    public function buildNestedSchema(array $rawRules): array
    {
        $tree = [];

        foreach ($rawRules as $field => $rules) {
            $parts = explode('.', $field);
            $current = &$tree;

            $rulesString = implode('|', $rules);
            $type = $this->inferTypeFromRules($rulesString);
            $required = str_contains($rulesString, 'required');

            $count = count($parts);
            for ($i = 0; $i < $count; $i++) {
                $part = $parts[$i];
                $isLast = ($i === $count - 1);

                // Handle array wildcard '*'
                if ($part === '*') {
                    if (isset($current['type'])) {
                        $current['type'] = 'array';
                    }
                    // Wildcard properties will attach directly to items definition
                    if (!isset($current['items'])) {
                        $current['items'] = [
                            'type' => 'any',
                            'required' => false,
                            'properties' => []
                        ];
                    }
                    $current = &$current['items'];
                    continue;
                }

                if ($isLast) {
                    if (isset($current['properties'])) {
                        if (isset($current['properties'][$part])) {
                            $current['properties'][$part]['type'] = ($current['properties'][$part]['type'] === 'object' || !empty($current['properties'][$part]['properties'])) ? 'object' : $type;
                            $current['properties'][$part]['required'] = $required;
                            $current['properties'][$part]['rules'] = $rules;
                        } else {
                            $current['properties'][$part] = [
                                'name' => $part,
                                'type' => $type,
                                'required' => $required,
                                'rules' => $rules,
                            ];
                        }
                    } else {
                        if (isset($current[$part])) {
                            $current[$part]['type'] = ($current[$part]['type'] === 'object' || !empty($current[$part]['properties'])) ? 'object' : $type;
                            $current[$part]['required'] = $required;
                            $current[$part]['rules'] = $rules;
                        } else {
                            $current[$part] = [
                                'name' => $part,
                                'type' => $type,
                                'required' => $required,
                                'rules' => $rules,
                            ];
                        }
                    }
                } else {
                    // Intermediate nested layer
                    if (isset($current['properties'])) {
                        if (!isset($current['properties'][$part])) {
                            $current['properties'][$part] = [
                                'name' => $part,
                                'type' => 'object',
                                'required' => false,
                                'properties' => []
                            ];
                        } else {
                            $current['properties'][$part]['type'] = 'object';
                            if (!isset($current['properties'][$part]['properties'])) {
                                $current['properties'][$part]['properties'] = [];
                            }
                        }
                        $current = &$current['properties'][$part];
                    } else {
                        if (!isset($current[$part])) {
                            $current[$part] = [
                                'name' => $part,
                                'type' => 'object',
                                'required' => false,
                                'properties' => []
                            ];
                        } else {
                            $current[$part]['type'] = 'object';
                            if (!isset($current[$part]['properties'])) {
                                $current[$part]['properties'] = [];
                            }
                        }
                        $current = &$current[$part];
                    }
                }
            }
            unset($current);
        }

        return $tree;
    }

    /**
     * Infer the typescript/schema type from the validation ruleset.
     */
    protected function inferTypeFromRules(string $rulesString): string
    {
        if (str_contains($rulesString, 'integer') || str_contains($rulesString, 'numeric')) {
            return 'number';
        }
        if (str_contains($rulesString, 'boolean') || str_contains($rulesString, 'bool')) {
            return 'boolean';
        }
        if (str_contains($rulesString, 'array')) {
            return 'array';
        }
        return 'string';
    }

    /**
     * Dynamic fallback route name generator when route name is undefined.
     */
    protected function generateRouteName(string $uri, array $methods): string
    {
        $method = count($methods) > 0 ? $methods[0] : 'GET';
        $cleanedUri = preg_replace('/[^A-Za-z0-9]/', ' ', $uri);
        return strtolower($method) . '.' . str_replace(' ', '.', trim($cleanedUri));
    }
}
