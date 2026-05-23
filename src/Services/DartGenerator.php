<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Services;

class DartGenerator extends BaseSchemaGenerator
{
    protected array $subClasses = [];

    public function generate(array $apiRoutes): string
    {
        $dart = "/** Auto-Generated via Laravel API Blueprint **/\n\nimport 'dart:convert';\n\n";

        foreach ($apiRoutes as $route) {
            if (empty($route['nested_rules'])) {
                continue;
            }

            $this->subClasses = [];
            $rootTypeName = $this->cleanTypeName($route['name']) . 'Request';

            $dart .= $this->buildDartClass($rootTypeName, $route['nested_rules']);

            // Append nested sub-classes
            if (!empty($this->subClasses)) {
                foreach ($this->subClasses as $subClass) {
                    $dart .= $subClass;
                }
            }
        }

        return $dart;
    }

    protected function buildDartClass(string $className, array $properties): string
    {
        $classStr = "class {$className} {\n";

        // 1. Fields
        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';
            $required = $prop['required'] ?? false;
            $dartFieldName = $this->toCamelCase($name);
            $dartType = $this->getDartType($type, $name, $prop, $className);
            $nullable = $required ? '' : '?';
            
            $classStr .= "  final {$dartType}{$nullable} {$dartFieldName};\n";
        }

        $classStr .= "\n";

        // 2. Constructor
        $classStr .= "  {$className}({\n";
        foreach ($properties as $name => $prop) {
            $required = $prop['required'] ?? false;
            $dartFieldName = $this->toCamelCase($name);
            $prefix = $required ? 'required ' : '';
            $classStr .= "    {$prefix}this.{$dartFieldName},\n";
        }
        $classStr .= "  });\n\n";

        // 3. fromJson
        $classStr .= "  factory {$className}.fromJson(Map<String, dynamic> json) => {$className}(\n";
        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';
            $dartFieldName = $this->toCamelCase($name);
            
            $jsonResolution = "json['{$name}']";
            if ($type === 'object') {
                $subName = $className . $this->cleanTypeName($name);
                $jsonResolution = "json['{$name}'] != null ? {$subName}.fromJson(json['{$name}']) : null";
            } elseif ($type === 'array') {
                if (isset($prop['items']) && !empty($prop['items']['properties'])) {
                    $subName = $className . $this->cleanTypeName($name) . 'Item';
                    $jsonResolution = "json['{$name}'] != null ? List<{$subName}>.from(json['{$name}'].map((x) => {$subName}.fromJson(x))) : null";
                } elseif (isset($prop['items'])) {
                    $itemType = $prop['items']['type'];
                    $dartItem = ($itemType === 'number') ? 'double' : (($itemType === 'boolean') ? 'bool' : 'String');
                    $jsonResolution = "json['{$name}'] != null ? List<{$dartItem}>.from(json['{$name}']) : null";
                }
            }

            $classStr .= "    {$dartFieldName}: {$jsonResolution},\n";
        }
        $classStr .= "  );\n\n";

        // 4. toJson
        $classStr .= "  Map<String, dynamic> toJson() => {\n";
        foreach ($properties as $name => $prop) {
            $type = $prop['type'] ?? 'string';
            $dartFieldName = $this->toCamelCase($name);
            
            $jsonValue = $dartFieldName;
            if ($type === 'object') {
                $jsonValue = "{$dartFieldName}?.toJson()";
            } elseif ($type === 'array' && isset($prop['items']) && !empty($prop['items']['properties'])) {
                $jsonValue = "{$dartFieldName} != null ? List<dynamic>.from({$dartFieldName}!.map((x) => x.toJson())) : null";
            }

            $classStr .= "    '{$name}': {$jsonValue},\n";
        }
        $classStr .= "  };\n";

        $classStr .= "}\n\n";

        return $classStr;
    }

    protected function getDartType(string $type, string $name, array $prop, string $parentClass): string
    {
        if ($type === 'number') {
            return 'double';
        }
        if ($type === 'boolean') {
            return 'bool';
        }
        if ($type === 'object') {
            $subClassName = $parentClass . $this->cleanTypeName($name);
            $subProperties = $prop['properties'] ?? [];
            $this->subClasses[] = $this->buildDartClass($subClassName, $subProperties);
            return $subClassName;
        }
        if ($type === 'array') {
            if (isset($prop['items']) && !empty($prop['items']['properties'])) {
                $subClassName = $parentClass . $this->cleanTypeName($name) . 'Item';
                $subProperties = $prop['items']['properties'] ?? [];
                $this->subClasses[] = $this->buildDartClass($subClassName, $subProperties);
                return "List<{$subClassName}>";
            }
            if (isset($prop['items'])) {
                $itemType = $prop['items']['type'];
                $dartItem = ($itemType === 'number') ? 'double' : (($itemType === 'boolean') ? 'bool' : 'String');
                return "List<{$dartItem}>";
            }
            return 'List<dynamic>';
        }
        return 'String';
    }

    protected function toCamelCase(string $string): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
        $studly = str_replace(' ', '', ucwords(strtolower($cleaned)));
        return lcfirst($studly);
    }
}
