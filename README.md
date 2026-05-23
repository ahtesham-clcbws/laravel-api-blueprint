<p align="center">
  <img src="https://img.shields.io/badge/Laravel%20API%20Blueprint-v1.0.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel API Blueprint" height="50">
</p>

# Laravel API Blueprint

[![Latest Version on Packagist](https://img.shields.io/packagist/v/clcbws/laravel-api-blueprint.svg?style=flat-square)](https://packagist.org/packages/clcbws/laravel-api-blueprint)
[![Total Downloads](https://img.shields.io/packagist/dt/clcbws/laravel-api-blueprint.svg?style=flat-square)](https://packagist.org/packages/clcbws/laravel-api-blueprint)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

**Laravel API Blueprint** (`clcbws/laravel-api-blueprint`) is a zero-dependency, ultra-lightweight, and highly robust documentation and code-generation engine designed for modern Laravel APIs. It automatically parses active API routes, safely extracts payload validation schemas directly from custom `FormRequest` injects using native PHP reflection, compiles full OpenAPI specifications and Postman Collections, and dynamically generates typed data structures for **5 major programming languages**.

---

## 🌟 Core Features & Highlights

### 1. Zero Bloat & Zero External Dependencies
Avoid heavy annotation parsers or bloated libraries (such as Swagger-PHP or L5-Swagger). Laravel API Blueprint relies entirely on PHP's native reflection capabilities and built-in Laravel utilities, keeping your vendor footprints completely clean.

### 2. Resilient Reflection Context Mocking
Laravel resolves `FormRequest` classes from the Service Container during their resolution cycle. By default, this triggers Laravel's `afterResolving` hooks which immediately run validation on empty incoming request parameters, throwing a fatal `ValidationException` (e.g. *"The email field is required"*). 
Laravel API Blueprint solves this completely by **manually instantiating request objects** (`new $className()`) and **manually setting the Service Container reference** (`$request->setContainer(Container::getInstance())`). This safely bypasses auto-execution triggers while ensuring the request retains full container awareness for looking up custom rules, translations, and database scopes!

### 3. Dynamic Dot-Notation Schema Mapper
Validation schemas are often defined using flat dot-notation structures (e.g. `profile` as `array`, alongside child rules `profile.bio` or `items.*.price`). The Route Parser recursively translates these flat definitions into beautiful, multi-dimensional, nested schema objects and array structures, automatically upgrading array types to object definitions if they contain named children.

### 4. Interactive In-Browser Exporters (The Glassmorphism Drawer)
Inside the interactive documentation web UI, users can slide out a glowing Glassmorphism control panel with live tabs to instantly view and copy generated payload schemas in **5 client-side languages**:
1.  **TypeScript**: Nested type-safe `interface` models.
2.  **Swift**: Nested iOS `Codable` structs.
3.  **Java**: Immutable, modern Java 14+ `record` schemas with Jackson annotations.
4.  **Dart**: Flutter-compatible models complete with factory `fromJson` and standard `toJson` serialization/deserialization utilities.
5.  **Go**: Idiomatic Go struct definitions featuring standard `json:"...,omitempty"` structure tags.

### 5. Postman Collection & OpenAPI Compliant Specification Exporter
Compiles fully compliant **OpenAPI 3.1.0 specifications** and structured **Postman v2.1.0 Collections**. Mapped endpoints automatically convert Laravel route variables (e.g. `users/{user}`) into Postman-native path variables (`:user`) and prepopulate body templates with structured JSON mock payloads.

### 6. Sub-Millisecond Serialization Caching
In production environments, scanning classes and running reflection on every page view degrades performance. The package features a lightweight file serialization caching engine that saves compiled OpenAPI JSON schemas directly to the server's cache, ensuring sub-millisecond route execution speeds.

---

## 📦 Installation

Add the package to your Laravel application via Composer (ideally in your development scope):

```bash
composer require clcbws/laravel-api-blueprint --dev
```

Once installed, publish the configuration blueprint:

```bash
php artisan vendor:publish --provider="LaravelApiBlueprint\Providers\ApiBlueprintServiceProvider" --tag="api-blueprint-config"
```

---

## ⚙️ Configuration Options

The default configuration file is published at `config/api-blueprint.php`. Below is a comprehensive breakdown of each configuration key:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Global Enable/Disable Toggle
    |--------------------------------------------------------------------------
    | Set to false to disable documentation endpoints and commands completely.
    */
    'enabled' => env('API_BLUEPRINT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Routing Dashboard Path
    |--------------------------------------------------------------------------
    | The base URI where your documentation will be served (e.g., http://your-app.test/api-blueprint).
    */
    'path' => env('API_BLUEPRINT_PATH', 'api-blueprint'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware Stack
    |--------------------------------------------------------------------------
    | Middleware applied to the docs viewer and schema routes. Custom middleware
    | or authentication guards can be added here.
    */
    'middleware' => [
        LaravelApiBlueprint\Http\Middleware\GatedDocAccess::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Gated Access Security Control
    |--------------------------------------------------------------------------
    | You can secure your endpoints using standard HTTP basic auth credentials,
    | or define a custom Laravel authorization Gate (e.g., 'view-api-docs')
    | for session and role-based checks.
    */
    'auth' => [
        'username' => env('API_BLUEPRINT_USERNAME', 'admin'),
        'password' => env('API_BLUEPRINT_PASSWORD', 'secret'),
        
        // Example: Gate::allows('view-api-docs')
        'gate' => null, 
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Inspection Filters
    |--------------------------------------------------------------------------
    | Define the prefix scopes. Only routes matching these prefix filters will
    | be parsed and compiled by the documentation engine.
    */
    'route_prefixes' => ['api/'],

    /*
    |--------------------------------------------------------------------------
    | High-Performance Spec Caching
    |--------------------------------------------------------------------------
    | Set to true to cache the compiled specification file on disk, avoiding
    | parsing controller files on every web view.
    */
    'cache_enabled' => env('API_BLUEPRINT_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Structural Exporter Target Outputs
    |--------------------------------------------------------------------------
    | Path locations where Postman collection and programming language schemas
    | will be compiled and dumped when running the export command.
    */
    'outputs' => [
        'postman_path'    => storage_path('app/api-blueprint/postman_collection.json'),
        'typescript_path' => storage_path('app/api-blueprint/api.d.ts'),
        'swift_path'      => storage_path('app/api-blueprint/api.swift'),
        'java_path'       => storage_path('app/api-blueprint/dto'),
        'dart_path'       => storage_path('app/api-blueprint/api.dart'),
        'go_path'         => storage_path('app/api-blueprint/api.go'),
    ],
];
```

---

## 🚀 How to Use

### 1. View Interactive Documentation Dashboard
Navigate directly to your configured path (e.g., `http://your-app.test/api-blueprint`).
1. **Interactive Visualizer**: Elements dashboard lets you explore active HTTP request methods, headers, parameters, and payloads.
2. **Glassmorphism Exporter Drawer**: Click the glowing **Client Schemas** button in the header. A blurred overlay slides open from the right, allowing you to select and copy perfectly formatted payload schemas for **TypeScript, Swift, Java, Dart, and Go** computed on the fly.
3. **Theme Switcher**: Instantly toggle between a custom dark/light premium aesthetic (persisted in local storage).

### 2. Export Client Schemas and Artifacts via CLI
You can compile the entire set of Postman collections, OpenAPI specs, and the 5 language models directly into your storage outputs by running:

```bash
php artisan blueprint:export
```

On success, the console logs the output directories:
```text
Parsing runtime route structures...
Postman Collection dumped to: storage/app/api-blueprint/postman_collection.json
TypeScript Interfaces dumped to: storage/app/api-blueprint/api.d.ts
Swift Codable Structs dumped to: storage/app/api-blueprint/api.swift
Java DTO Records dumped to: storage/app/api-blueprint/dto/ApiDTOs.java
Dart Serialization Models dumped to: storage/app/api-blueprint/api.dart
Go Structs dumped to: storage/app/api-blueprint/api.go
All API schema artifacts successfully compiled and exported.
```

---

## 📁 Package Architecture & Directory Structure

```text
src/
├── Providers/
│   └── ApiBlueprintServiceProvider.php        # Bootstraps package configs, views, commands, and routes
├── Http/
│   ├── Controllers/
│   │   └── ApiBlueprintController.php         # Serves UI view and schema JSON (integrated with disk caching)
│   └── Middleware/
│       └── GatedDocAccess.php                 # Enforces security credentials using basic auth or custom Gates
├── Services/
│   ├── BaseSchemaGenerator.php                # Base class defining identifier cleaning and name sanitizing
│   ├── RouteParser.php                        # Reflects controller methods, resolves requests, maps dot-notation
│   ├── OpenApiSpecGenerator.php               # Compiles compliant OpenAPI 3.1.0 specification files
│   ├── PostmanGenerator.php                   # Formulates Postman collections with path variables and request bodies
│   ├── TypeScriptGenerator.php                # Formats typescript interfaces
│   ├── SwiftGenerator.php                     # Formats Swift Codable models
│   ├── JavaGenerator.php                      # Formats Java 14+ Record structures
│   ├── DartGenerator.php                      # Formats Dart Flutter serializable classes
│   └── GoGenerator.php                        # Formats json-tagged Go struct schemas
└── Commands/
    └── ExportApiArtifacts.php                 # Artisan CLI exporter command execution
```

---

## 🧪 Automated Testing

Laravel API Blueprint is fully covered by an automated test suite verifying:
- Safe container instantiation of custom `FormRequest` elements.
- Multi-dimensional translation of dot-notation rule structures.
- Structural layout outputs for **TypeScript, Swift, Java, Dart, and Go**.
- Compilation accuracy of Postman Collections and OpenAPI specs.

Verify the test suite locally in your environment:

```bash
vendor/bin/phpunit tests/Feature/ApiBlueprintTest.php
```

Output:
```text
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.6
.
..
...
....
.....
......
.......
........
.........                                                           9 / 9 (100%)

Time: 00:00.238, Memory: 10.00 MB
OK (9 tests, 35 assertions)
```

---

## 📄 License

This package is licensed under the MIT License. See [LICENSE.md](LICENSE.md) for details.
