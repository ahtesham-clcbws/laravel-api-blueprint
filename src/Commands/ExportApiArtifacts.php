<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LaravelApiBlueprint\Services\RouteParser;
use LaravelApiBlueprint\Services\PostmanGenerator;
use LaravelApiBlueprint\Services\TypeScriptGenerator;
use LaravelApiBlueprint\Services\SwiftGenerator;
use LaravelApiBlueprint\Services\JavaGenerator;
use LaravelApiBlueprint\Services\DartGenerator;
use LaravelApiBlueprint\Services\GoGenerator;

class ExportApiArtifacts extends Command
{
    protected $signature = 'blueprint:export';
    protected $description = 'Generate and dump Postman collection and structural technology schemas (TS, Swift, Java, Dart, Go) from active API routes';

    public function handle(
        RouteParser $parser,
        PostmanGenerator $postman,
        TypeScriptGenerator $typescript,
        SwiftGenerator $swift,
        JavaGenerator $java,
        DartGenerator $dart,
        GoGenerator $go
    ): int {
        $this->info('Parsing runtime route structures...');
        $routes = $parser->getApiRoutes();

        if (empty($routes)) {
            $this->warn('No API routes discovered. Ensure your route filters configuration is correct.');
            return Command::SUCCESS;
        }

        $outputs = config('api-blueprint.outputs', []);

        // 1. Postman Collection
        if (isset($outputs['postman_path'])) {
            $path = $outputs['postman_path'];
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $postman->generate($routes));
            $this->info("Postman Collection dumped to: {$path}");
        }

        // 2. TypeScript Interfaces
        if (isset($outputs['typescript_path'])) {
            $path = $outputs['typescript_path'];
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $typescript->generate($routes));
            $this->info("TypeScript Interfaces dumped to: {$path}");
        }

        // 3. Swift Codable Structs
        if (isset($outputs['swift_path'])) {
            $path = $outputs['swift_path'];
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $swift->generate($routes));
            $this->info("Swift Codable Structs dumped to: {$path}");
        }

        // 4. Java DTO Records
        if (isset($outputs['java_path'])) {
            $path = $outputs['java_path'];
            // If it points to a directory, append standard filename
            if (is_dir($path) || !str_ends_with($path, '.java')) {
                File::ensureDirectoryExists($path);
                $path = rtrim($path, '/') . '/ApiDTOs.java';
            } else {
                File::ensureDirectoryExists(dirname($path));
            }
            File::put($path, $java->generate($routes));
            $this->info("Java DTO Records dumped to: {$path}");
        }

        // 5. Dart Serialization Models
        if (isset($outputs['dart_path'])) {
            $path = $outputs['dart_path'];
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $dart->generate($routes));
            $this->info("Dart Serialization Models dumped to: {$path}");
        }

        // 6. Go JSON Structs
        if (isset($outputs['go_path'])) {
            $path = $outputs['go_path'];
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $go->generate($routes));
            $this->info("Go Structs dumped to: {$path}");
        }

        $this->info('All API schema artifacts successfully compiled and exported.');
        return Command::SUCCESS;
    }
}
