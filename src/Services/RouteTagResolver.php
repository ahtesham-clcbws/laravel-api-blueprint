<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

use Illuminate\Support\Facades\Config;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class RouteTagResolver
{
    /**
     * Resolve the tags/groups for a given route controller, method, and URI.
     *
     * @param string $controller The FQCN of the controller.
     * @param string $method     The controller method name.
     * @param string $uri        The route URI.
     * @return array
     */
    public function resolve(string $controller, string $method, string $uri): array
    {
        $baseTags = $this->resolveBaseTags($controller, $method, $uri);

        // Detect version segment (e.g. v1, v2, v3) in URI
        $clean = ltrim($uri, '/');
        if (str_starts_with($clean, 'api/')) {
            $clean = substr($clean, 4);
        }
        $segments = explode('/', $clean);
        $version = null;
        foreach ($segments as $segment) {
            if (preg_match('/^v[0-9]+$/i', $segment)) {
                $version = strtoupper($segment); // e.g. V1
                break;
            }
        }

        // If version prefix is found, prepend it to all resolved tags
        if ($version !== null) {
            return array_map(fn($tag) => "{$version} / {$tag}", $baseTags);
        }

        return $baseTags;
    }

    /**
     * Resolve base tags without version prefix.
     */
    protected function resolveBaseTags(string $controller, string $method, string $uri): array
    {
        // 1. Manual Config Mappings (Glob-based pattern matches)
        $configGroups = Config::get('api-blueprint.groups', []);
        foreach ($configGroups as $pattern => $groupName) {
            // If pattern contains namespace separator, match against controller class FQCN
            if (str_contains($pattern, '\\')) {
                if (fnmatch($pattern, $controller)) {
                    return [$groupName];
                }
            } else {
                // Otherwise, match against the URI (with or without api/ prefix)
                $cleanPattern = ltrim($pattern, '/');
                $cleanUri = ltrim($uri, '/');
                if (fnmatch($cleanPattern, $cleanUri) || fnmatch($cleanPattern, 'api/' . $cleanUri)) {
                    return [$groupName];
                }
            }
        }

        try {
            $reflectionClass = new ReflectionClass($controller);
            $reflectionMethod = new ReflectionMethod($controller, $method);

            // 2. Method Attribute (Highly specific PHP 8 native attribute)
            $methodAttrs = $reflectionMethod->getAttributes();
            foreach ($methodAttrs as $attr) {
                $attrName = $attr->getName();
                if ($attrName === 'LaravelApiBlueprint\Attributes\Group' ||
                    str_ends_with($attrName, '\\Group')
                ) {
                    $args = $attr->getArguments();
                    if (!empty($args)) {
                        $name = reset($args);
                        if (is_string($name)) {
                            return [$name];
                        }
                    }
                }
            }

            // 3. Method PHPDoc (Method-level annotation)
            $methodDoc = $reflectionMethod->getDocComment();
            if ($methodDoc !== false) {
                if (preg_match('/@group\s+([^\n\r]+)/', $methodDoc, $match)) {
                    return [trim($match[1])];
                }
                if (preg_match('/@tags?\s+([^\n\r]+)/', $methodDoc, $match)) {
                    return array_map('trim', explode(',', $match[1]));
                }
            }

            // 4. Class Attribute (Class-level PHP 8 native attribute)
            $classAttrs = $reflectionClass->getAttributes();
            foreach ($classAttrs as $attr) {
                $attrName = $attr->getName();
                if ($attrName === 'LaravelApiBlueprint\Attributes\Group' ||
                    str_ends_with($attrName, '\\Group')
                ) {
                    $args = $attr->getArguments();
                    if (!empty($args)) {
                        $name = reset($args);
                        if (is_string($name)) {
                            return [$name];
                        }
                    }
                }
            }

            // 5. Class PHPDoc (Class-level annotation)
            $classDoc = $reflectionClass->getDocComment();
            if ($classDoc !== false) {
                if (preg_match('/@group\s+([^\n\r]+)/', $classDoc, $match)) {
                    return [trim($match[1])];
                }
                if (preg_match('/@tags?\s+([^\n\r]+)/', $classDoc, $match)) {
                    return array_map('trim', explode(',', $match[1]));
                }
            }

        } catch (Throwable $e) {
            // Fail gracefully
        }

        // 6. Intelligent Fallback (Controller name clean & pluralize)
        $classParts = explode('\\', $controller);
        $className = end($classParts);
        $baseControllerName = str_replace('Controller', '', $className);

        if ($baseControllerName !== '' && !in_array(strtolower($baseControllerName), ['controller', 'base', 'api'])) {
            $name = $baseControllerName;
            $lower = strtolower($name);
            // Pluralize common resource names
            if (in_array($lower, ['user', 'product', 'order', 'employee', 'role', 'permission', 'customer', 'item', 'category', 'task'])) {
                $name = $name . 's';
            }
            return [$name];
        }

        // 7. Intelligent Fallback (URI Segment version-skipping)
        $clean = ltrim($uri, '/');
        if (str_starts_with($clean, 'api/')) {
            $clean = substr($clean, 4);
        }
        $segments = explode('/', $clean);
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }
            // Skip API version prefixes (e.g. v1, v2, v3)
            if (preg_match('/^v[0-9]+$/i', $segment)) {
                continue;
            }
            // Skip dynamic route path variables
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                continue;
            }
            return [ucfirst($segment)];
        }

        return ['General'];
    }
}
