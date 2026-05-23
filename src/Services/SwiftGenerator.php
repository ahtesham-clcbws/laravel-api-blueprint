<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

class SwiftGenerator extends BaseSchemaGenerator
{
    protected array $subStructs = [];

    public function generate(array $apiRoutes): string
    {
        $swift = "/** Auto-Generated via Laravel API Blueprint **/\n\nimport Foundation\n\n";

        foreach ($apiRoutes as $route) {
            if (empty($route['nested_rules'])) {
                continue;
            }

            $this->subStructs = [];
            $rootTypeName = $this->cleanTypeName($route['name']) . 'Request';

            $propertiesBlock = $this->renderProperties($route['nested_rules'], $rootTypeName, '    ');

            $swift .= "struct {$rootTypeName}: Codable {\n";
            $swift .= $propertiesBlock;
            
            // Append nested sub-structs
            if (!empty($this->subStructs)) {
                $swift .= "\n";
                foreach ($this->subStructs as $subStruct) {
                    $swift .= $subStruct;
                }
            }

            $swift .= "}\n\n";
        }

        return $swift;
    }

    protected function renderProperties(array $properties, string $parentName, string $indent): string
    {
        $output = '';

        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';
            $required = $prop['required'] ?? false;
            $optionalMarker = $required ? '' : '?';

            $swiftFieldName = $this->toCamelCase($name);
            $swiftType = 'String';

            if ($type === 'number') {
                $swiftType = 'Double';
            } elseif ($type === 'boolean') {
                $swiftType = 'Bool';
            } elseif ($type === 'object') {
                $subStructName = $parentName . $this->cleanTypeName($name);
                $swiftType = $subStructName;

                // Build sub-struct definition
                $subProperties = $prop['properties'] ?? [];
                $subBlock = $this->renderProperties($subProperties, $subStructName, '        ');
                
                $structDef = "{$indent}struct {$subStructName}: Codable {\n";
                $structDef .= $subBlock;
                $structDef .= "{$indent}}\n\n";
                
                $this->subStructs[] = $structDef;

            } elseif ($type === 'array') {
                if (isset($prop['items']) && !empty($prop['items']['properties'])) {
                    $subStructName = $parentName . $this->cleanTypeName($name) . 'Item';
                    $swiftType = "[{$subStructName}]";

                    $subProperties = $prop['items']['properties'] ?? [];
                    $subBlock = $this->renderProperties($subProperties, $subStructName, '        ');
                    
                    $structDef = "{$indent}struct {$subStructName}: Codable {\n";
                    $structDef .= $subBlock;
                    $structDef .= "{$indent}}\n\n";
                    
                    $this->subStructs[] = $structDef;
                } elseif (isset($prop['items'])) {
                    $itemType = $prop['items']['type'];
                    $swiftItem = ($itemType === 'number') ? 'Double' : (($itemType === 'boolean') ? 'Bool' : 'String');
                    $swiftType = "[{$swiftItem}]";
                } else {
                    $swiftType = '[String]';
                }
            }

            $output .= "{$indent}let {$swiftFieldName}: {$swiftType}{$optionalMarker}\n";
        }

        return $output;
    }

    protected function toCamelCase(string $string): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
        $studly = str_replace(' ', '', ucwords(strtolower($cleaned)));
        return lcfirst($studly);
    }
}
