<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

class GoGenerator extends BaseSchemaGenerator
{
    protected array $subStructs = [];

    public function generate(array $apiRoutes): string
    {
        $go = "/** Auto-Generated via Laravel API Blueprint **/\n\npackage dto\n\n";

        foreach ($apiRoutes as $route) {
            if (empty($route['nested_rules'])) {
                continue;
            }

            $this->subStructs = [];
            $rootTypeName = $this->cleanTypeName($route['name']) . 'Request';

            $structBlock = $this->renderFields($route['nested_rules'], $rootTypeName, "\t");

            $go .= "type {$rootTypeName} struct {\n";
            $go .= $structBlock;
            $go .= "}\n\n";

            // Append nested sub-structs
            if (!empty($this->subStructs)) {
                foreach ($this->subStructs as $subStruct) {
                    $go .= $subStruct;
                }
            }
        }

        return $go;
    }

    protected function renderFields(array $properties, string $parentName, string $indent): string
    {
        $output = '';

        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';
            $required = $prop['required'] ?? false;
            $optionalPointer = $required ? '' : '*';
            $jsonOmitEmpty = $required ? '' : ',omitempty';

            $goFieldName = $this->toPascalCase($name);
            $goType = 'string';

            if ($type === 'number') {
                $goType = 'float64';
            } elseif ($type === 'boolean') {
                $goType = 'bool';
            } elseif ($type === 'object') {
                $subStructName = $parentName . $this->cleanTypeName($name);
                $goType = $subStructName;

                $subProperties = $prop['properties'] ?? [];
                $subBlock = $this->renderFields($subProperties, $subStructName, "\t");
                
                $structDef = "type {$subStructName} struct {\n";
                $structDef .= $subBlock;
                $structDef .= "}\n\n";
                
                $this->subStructs[] = $structDef;

            } elseif ($type === 'array') {
                if (isset($prop['items']) && !empty($prop['items']['properties'])) {
                    $subStructName = $parentName . $this->cleanTypeName($name) . 'Item';
                    $goType = "[]{$subStructName}";

                    $subProperties = $prop['items']['properties'] ?? [];
                    $subBlock = $this->renderFields($subProperties, $subStructName, "\t");
                    
                    $structDef = "type {$subStructName} struct {\n";
                    $structDef .= $subBlock;
                    $structDef .= "}\n\n";
                    
                    $this->subStructs[] = $structDef;
                } elseif (isset($prop['items'])) {
                    $itemType = $prop['items']['type'];
                    $goItem = ($itemType === 'number') ? 'float64' : (($itemType === 'boolean') ? 'bool' : 'string');
                    $goType = "[]{$goItem}";
                } else {
                    $goType = '[]string';
                }
            }

            // Set optional pointer indicator safely (except for array slices)
            $typePrefix = str_starts_with($goType, '[]') ? '' : $optionalPointer;

            $output .= "{$indent}{$goFieldName} {$typePrefix}{$goType} `json:\"{$name}{$jsonOmitEmpty}\"`\n";
        }

        return $output;
    }

    protected function toPascalCase(string $string): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
        return str_replace(' ', '', ucwords(strtolower($cleaned)));
    }
}
