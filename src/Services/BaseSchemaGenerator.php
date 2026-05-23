<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

abstract class BaseSchemaGenerator
{
    /**
     * Convert Route Name or URI into a valid clean PascalCase model/type identifier.
     */
    public function cleanTypeName(string $name): string
    {
        // 1. Strip path parameters e.g. {user} or {id}
        $name = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $name);

        // 2. Replace non-alphanumeric characters with spaces
        $name = preg_replace('/[^A-Za-z0-9]/', ' ', $name);

        // 3. PascalCase conversion
        $pascal = str_replace(' ', '', ucwords(strtolower($name)));

        // 4. Ensure name begins with a letter
        if (preg_match('/^[0-9]/', $pascal)) {
            $pascal = 'Model' . $pascal;
        }

        return $pascal ?: 'RequestPayload';
    }

    /**
     * Generate the complete package definition or interfaces file for the technology.
     */
    abstract public function generate(array $apiRoutes): string;
}
