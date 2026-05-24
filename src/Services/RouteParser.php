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

                // Automatically scan code body to extract returned JSON keys (Scramble-equivalent)
                $filename = $reflection->getFileName();
                if ($filename && file_exists($filename)) {
                    $fileContent = file_get_contents($filename);
                    $fileLines = explode("\n", $fileContent);
                    $start = $reflection->getStartLine() - 1;
                    $end = $reflection->getEndLine();
                    $methodCode = implode("\n", array_slice($fileLines, $start, $end - $start));

                    // Match return response()->json([ ... ]) or Response::json([ ... ]) or response([ ... ])
                    if (preg_match_all('/(?:response\(\)->json|Response::json|response)\s*\(\s*/s', $methodCode, $responseMatches, PREG_OFFSET_CAPTURE)) {
                        foreach ($responseMatches[0] as $match) {
                            $offset = $match[1] + strlen($match[0]);
                            $arrayContent = $this->extractBalancedArrayString($methodCode, $offset);
                            if ($arrayContent !== null) {
                                // Extract status code if any after the array
                                $afterArray = substr($methodCode, $offset + strlen($arrayContent) + 2); // skip starting '[' and ending ']'
                                $statusCode = (str_contains(strtolower($method), 'store') ? '201' : '200');
                                if (preg_match('/^\s*,\s*(\d+)/', $afterArray, $statusMatches)) {
                                    $statusCode = $statusMatches[1];
                                }

                                $keys = $this->parseTopLevelKeys($arrayContent);

                                if (!empty($keys)) {
                                    if (!isset($customResponses[$statusCode])) {
                                        $customResponses[$statusCode] = [
                                            'description' => $statusCode === '201' ? 'Resource created successfully' : 'Successful operation'
                                        ];
                                    }
                                    if (!isset($customResponses[$statusCode]['schema_properties'])) {
                                        $customResponses[$statusCode]['schema_properties'] = [];
                                    }
                                    foreach ($keys as $key) {
                                        $type = 'string';
                                        if (in_array($key, ['user', 'order', 'product', 'data'])) {
                                            $type = 'object';
                                        } elseif (in_array($key, ['users', 'orders', 'products', 'items', 'results'])) {
                                            $type = 'array';
                                        }
                                        $customResponses[$statusCode]['schema_properties'][$key] = [
                                            'type' => $type
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fail-safe
            }

            // Extract and verify route middleware to determine if authentication is required (Scramble-equivalent)
            $middlewares = method_exists($route, 'gatherMiddleware') ? $route->gatherMiddleware() : [];
            $authRequired = false;
            foreach ($middlewares as $mw) {
                if (is_string($mw)) {
                    if (str_contains($mw, 'auth') || str_contains($mw, 'AuthenticateApiToken')) {
                        $authRequired = true;
                        break;
                    }
                } elseif (is_object($mw)) {
                    $mwClass = get_class($mw);
                    if (str_contains($mwClass, 'AuthenticateApiToken') || str_contains($mwClass, 'auth')) {
                        $authRequired = true;
                        break;
                    }
                }
            }

            $apiRoutes[] = [
                'uri'          => $route->uri(),
                'methods'      => array_filter($route->methods(), fn($m) => $m !== 'HEAD'),
                'name'         => $route->getName() ?? $this->generateRouteName($route->uri(), $route->methods()),
                'summary'      => $summary !== '' ? $summary : ($route->getName() ?? $this->generateRouteName($route->uri(), $route->methods())),
                'description'  => $description,
                'responses'    => $customResponses,
                'auth_required'=> $authRequired,
                'raw_rules'    => $rawRules,
                'nested_rules' => $nestedSchema,
            ];
        }

        return $apiRoutes;
    }

    protected function extractValidationRules(string $controller, string $method, LaravelRoute $route): array
    {
        // 1. Try to find injected FormRequest first
        try {
            $reflection = new \ReflectionMethod($controller, $method);
            foreach ($reflection->getParameters() as $param) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $className = $type->getName();
                    if (is_subclass_of($className, FormRequest::class)) {
                        return $this->resolveRequestRulesInContext($className, $route);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fail gracefully
        }

        // 2. Fallback to inline validation extraction (Scramble-equivalent parser)
        try {
            $reflection = new \ReflectionMethod($controller, $method);
            $filename = $reflection->getFileName();
            if ($filename && file_exists($filename)) {
                $fileContent = file_get_contents($filename);
                $lines = explode("\n", $fileContent);
                
                // Isolate the method's body
                $start = $reflection->getStartLine() - 1;
                $end = $reflection->getEndLine();
                $methodCode = implode("\n", array_slice($lines, $start, $end - $start));
                
                // Match validate( or Validator::make( followed by an array rules block
                if (preg_match('/(?:validate|make)\s*\(\s*(?:(?:[^,\[]+?)\s*,\s*)?\[(.*?)\]/s', $methodCode, $matches)) {
                    $rulesBlock = $matches[1];
                    return $this->parseInlineRulesString($rulesBlock);
                }
            }
        } catch (\Throwable $e) {
            // Fail gracefully
        }

        return [];
    }

    /**
     * Decodes and parses string rules and array rules from an inline validation block.
     */
    protected function parseInlineRulesString(string $rulesBlock): array
    {
        $rules = [];
        // Match: 'field' => 'rules' or "field" => "rules" or 'field' => ['rule1', 'rule2']
        preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*(?:[\'"]([^\'"]+)[\'"]|\[(.*?)\])/s', $rulesBlock, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $field = $match[1];
            if (!empty($match[2])) {
                // String format rules (e.g. 'required|email|nullable')
                $rules[$field] = explode('|', $match[2]);
            } elseif (!empty($match[3])) {
                // Array format rules (e.g. ['required', 'email'])
                preg_match_all('/[\'"]([^\'"]+)[\'"]/', $match[3], $ruleMatches);
                $rules[$field] = $ruleMatches[1] ?? [];
            }
        }
        
        return $rules;
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

    /**
     * Extract balanced square bracket array from PHP source code.
     * This method scans the code starting from the $startOffset and counts opening and closing
     * square brackets. It returns the raw string content inside the first top-level balanced
     * array block, perfectly preserving nested array and function parameters.
     */
    protected function extractBalancedArrayString(string $code, int $startOffset): ?string
    {
        $length = strlen($code);
        $bracketCount = 0;
        $arrayStart = -1;

        // Iterate character-by-character starting at the matched response offset
        for ($i = $startOffset; $i < $length; $i++) {
            $char = $code[$i];
            if ($char === '[') {
                if ($bracketCount === 0) {
                    $arrayStart = $i; // Record the beginning of the outermost array
                }
                $bracketCount++;
            } elseif ($char === ']') {
                $bracketCount--;
                // Outermost balanced bracket match has closed
                if ($bracketCount === 0 && $arrayStart !== -1) {
                    return substr($code, $arrayStart + 1, $i - $arrayStart - 1);
                }
            }
        }
        return null;
    }

    /**
     * Parse top-level array keys from an array source representation.
     * Implements a state-machine that respects quoted strings, escaping, and bracket depth.
     * This completely prevents extracting nested key-value definitions (e.g. nested array properties),
     * isolating only the primary keys returned directly in the response payload.
     */
    protected function parseTopLevelKeys(string $arrayContent): array
    {
        $length = strlen($arrayContent);
        $bracketCount = 0;
        $inString = false;
        $stringChar = '';
        $keys = [];

        for ($i = 0; $i < $length; $i++) {
            $char = $arrayContent[$i];

            // 1. Detect quote boundary starts and ends while respecting backslash escaping
            if (($char === "'" || $char === '"') && ($i === 0 || $arrayContent[$i-1] !== '\\')) {
                if ($inString && $stringChar === $char) {
                    $inString = false;
                } elseif (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                }
                continue;
            }

            // 2. Ignore characters inside strings completely
            if ($inString) {
                continue;
            }

            // 3. Track bracket boundaries to ignore any inner/nested arrays or closures
            if ($char === '[' || $char === '(') {
                $bracketCount++;
            } elseif ($char === ']') {
                $bracketCount--;
            }

            // 4. If we are at the top-level, locate assignment arrows ("=>")
            if ($bracketCount === 0) {
                if ($char === '=' && $i + 1 < $length && $arrayContent[$i+1] === '>') {
                    $beforeArrow = substr($arrayContent, 0, $i);
                    // Extract the closest preceding quoted string before the arrow
                    if (preg_match('/[\'"]([^\'"]+)[\'"]\s*$/', $beforeArrow, $keyMatch)) {
                        $keys[] = $keyMatch[1];
                    }
                }
            }
        }

        return $keys;
    }
}
