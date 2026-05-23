# Development Standards & Coding Rules

This document outlines the coding standards, naming conventions, and best practices for the **Laravel API Blueprint** package.

## 1. PHP & Laravel Standards
- **PHP Version Target**: PHP 8.2+ (utilizing strict types, constructor property promotion, readonly properties, and match expressions).
- **Laravel Version Target**: Laravel 10.x / 11.x / 12.x compatible.
- **Strict Typing**: Every PHP file MUST begin with `declare(strict_types=1);`.
- **Return Type Hints**: All functions and methods must have explicit return type hints.
- **Type Safety**: Avoid using `mixed` or loose types unless absolutely necessary.

## 2. Modularity & Clean Code Principles
- **Single Responsibility Principle (SRP)**: Each class must have exactly one reason to change. Service classes (e.g., parsers, exporters) should be highly focused and not mix concerns.
- **Code Density Limits**:
  - Keep files focused and compact.
  - Ideal: < 200 lines (target for 90% of files).
  - Absolute limit: 500 lines.
- **No Heavy Third-Party Dependencies**: Leverage Laravel's built-in capabilities and native PHP reflection rather than adding external composer packages.

## 3. Naming Conventions
- **Namespaces**: Root namespace is `LaravelApiBlueprint\`.
- **Classes**: `PascalCase` (e.g., `TypeScriptGenerator`).
- **Methods & Variables**: `camelCase` (e.g., `extractValidationRules`).
- **Config Keys**: `snake_case` (e.g., `postman_path`).
- **Blade Views**: `kebab-case` (e.g., `docs.blade.php`).

## 4. Security & Performance Rules
- **Access Control Gating**: The documentation routes must be strictly gated using credential-based access control or extensible auth hooks (to prevent exposing internal schemas in production).
- **Reflection Caching**: Reflection can be slow. In production, parsing results must be cacheable or compiled to avoid high latency overhead during runtime requests.
