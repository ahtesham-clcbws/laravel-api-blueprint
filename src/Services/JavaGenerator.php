<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

class JavaGenerator extends BaseSchemaGenerator
{
    protected array $subRecords = [];

    public function generate(array $apiRoutes): string
    {
        $java = "/** Auto-Generated via Laravel API Blueprint **/\n\npackage com.blueprint.dto;\n\nimport java.util.List;\nimport com.fasterxml.jackson.annotation.JsonProperty;\n\n";

        foreach ($apiRoutes as $route) {
            if (empty($route['nested_rules'])) {
                continue;
            }

            $this->subRecords = [];
            $rootTypeName = $this->cleanTypeName($route['name']) . 'Request';

            $fieldsBlock = $this->renderFields($route['nested_rules'], $rootTypeName, '    ');

            $java .= "public record {$rootTypeName}(\n";
            $java .= $fieldsBlock;
            $java .= ") {\n";

            // Append nested sub-records
            if (!empty($this->subRecords)) {
                $java .= "\n";
                foreach ($this->subRecords as $subRecord) {
                    $java .= $subRecord;
                }
            }

            $java .= "}\n\n";
        }

        return $java;
    }

    protected function renderFields(array $properties, string $parentName, string $indent): string
    {
        $output = [];

        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';
            
            $javaFieldName = $this->toCamelCase($name);
            $javaType = 'String';

            if ($type === 'number') {
                $javaType = 'Double';
            } elseif ($type === 'boolean') {
                $javaType = 'Boolean';
            } elseif ($type === 'object') {
                $subRecordName = $parentName . $this->cleanTypeName($name);
                $javaType = $subRecordName;

                $subProperties = $prop['properties'] ?? [];
                $subBlock = $this->renderFields($subProperties, $subRecordName, '        ');
                
                $recordDef = "{$indent}public record {$subRecordName}(\n";
                $recordDef .= $subBlock;
                $recordDef .= "{$indent}) {}\n\n";
                
                $this->subRecords[] = $recordDef;

            } elseif ($type === 'array') {
                if (isset($prop['items']) && !empty($prop['items']['properties'])) {
                    $subRecordName = $parentName . $this->cleanTypeName($name) . 'Item';
                    $javaType = "List<{$subRecordName}>";

                    $subProperties = $prop['items']['properties'] ?? [];
                    $subBlock = $this->renderFields($subProperties, $subRecordName, '        ');
                    
                    $recordDef = "{$indent}public record {$subRecordName}(\n";
                    $recordDef .= $subBlock;
                    $recordDef .= "{$indent}) {}\n\n";
                    
                    $this->subRecords[] = $recordDef;
                } elseif (isset($prop['items'])) {
                    $itemType = $prop['items']['type'];
                    $javaItem = ($itemType === 'number') ? 'Double' : (($itemType === 'boolean') ? 'Boolean' : 'String');
                    $javaType = "List<{$javaItem}>";
                } else {
                    $javaType = 'List<String>';
                }
            }

            $output[] = "{$indent}@JsonProperty(\"{$name}\") {$javaType} {$javaFieldName}";
        }

        return implode(",\n", $output) . "\n";
    }

    protected function toCamelCase(string $string): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
        $studly = str_replace(' ', '', ucwords(strtolower($cleaned)));
        return lcfirst($studly);
    }
}
