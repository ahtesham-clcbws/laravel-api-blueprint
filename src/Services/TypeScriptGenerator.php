<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

class TypeScriptGenerator extends BaseSchemaGenerator
{
    public function generate(array $apiRoutes): string
    {
        $ts = "/** Auto-Generated via Laravel API Blueprint **/\n\n";

        foreach ($apiRoutes as $route) {
            if (empty($route['nested_rules'])) {
                continue;
            }

            $typeName = $this->cleanTypeName($route['name']) . 'Request';
            $ts .= "export interface {$typeName} {\n";
            $ts .= $this->renderProperties($route['nested_rules'], '    ');
            $ts .= "}\n\n";
        }

        return $ts;
    }

    protected function renderProperties(array $properties, string $indent): string
    {
        $output = '';

        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';
            $required = $prop['required'] ?? false;
            $optionalMarker = $required ? '' : '?';

            // Resolve types
            $tsType = 'string';
            if ($type === 'number') {
                $tsType = 'number';
            } elseif ($type === 'boolean') {
                $tsType = 'boolean';
            } elseif ($type === 'object') {
                if (!empty($prop['properties'])) {
                    $tsType = "{\n" . $this->renderProperties($prop['properties'], $indent . '    ') . $indent . "}";
                } else {
                    $tsType = 'Record<string, any>';
                }
            } elseif ($type === 'array') {
                if (isset($prop['items']) && !empty($prop['items']['properties'])) {
                    $tsType = "Array<{\n" . $this->renderProperties($prop['items']['properties'], $indent . '    ') . $indent . "}>";
                } elseif (isset($prop['items'])) {
                    $itemType = $prop['items']['type'];
                    $tsItem = ($itemType === 'number') ? 'number' : (($itemType === 'boolean') ? 'boolean' : 'string');
                    $tsType = "{$tsItem}[]";
                } else {
                    $tsType = 'any[]';
                }
            }

            $output .= "{$indent}{$name}{$optionalMarker}: {$tsType};\n";
        }

        return $output;
    }
}
