<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Tests\Feature;

use Orchestra\Testbench\TestCase;
use LaravelApiBlueprint\Services\RouteParser;
use LaravelApiBlueprint\Services\TypeScriptGenerator;
use LaravelApiBlueprint\Services\SwiftGenerator;
use LaravelApiBlueprint\Services\JavaGenerator;
use LaravelApiBlueprint\Services\DartGenerator;
use LaravelApiBlueprint\Services\GoGenerator;
use LaravelApiBlueprint\Services\OpenApiSpecGenerator;
use LaravelApiBlueprint\Services\PostmanGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;

class ApiBlueprintTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \LaravelApiBlueprint\Providers\ApiBlueprintServiceProvider::class
        ];
    }

    /**
     * Define routes setup.
     *
     * @param \Illuminate\Routing\Router $router
     */
    protected function defineRoutes($router): void
    {
        // Bind mock controllers & endpoints for the test suite
        $router->post('/api/users/store', [MockUserController::class, 'store'])->name('users.store');
        $router->get('/api/users/index', [MockUserController::class, 'index'])->name('users.index');
    }

    public function test_route_parser_extracts_api_routes_safely(): void
    {
        $parser = new RouteParser();
        $routes = $parser->getApiRoutes();

        $this->assertNotEmpty($routes);
        
        // Find store route
        $storeRoute = Collection::make($routes)->firstWhere('name', 'users.store');
        
        $this->assertNotNull($storeRoute);
        $this->assertEquals('api/users/store', $storeRoute['uri']);
        $this->assertContains('POST', $storeRoute['methods']);
        
        // Check dynamic validation rule parsing
        $this->assertArrayHasKey('email', $storeRoute['raw_rules']);
        $this->assertContains('required', $storeRoute['raw_rules']['email']);
        $this->assertContains('string', $storeRoute['raw_rules']['email']);
    }

    public function test_route_parser_maps_nested_rules_correctly(): void
    {
        $parser = new RouteParser();
        $routes = $parser->getApiRoutes();
        $storeRoute = Collection::make($routes)->firstWhere('name', 'users.store');

        $nested = $storeRoute['nested_rules'];

        $this->assertArrayHasKey('email', $nested);
        $this->assertEquals('string', $nested['email']['type']);
        $this->assertTrue($nested['email']['required']);

        $this->assertArrayHasKey('profile', $nested);
        $this->assertEquals('object', $nested['profile']['type']);
        $this->assertArrayHasKey('bio', $nested['profile']['properties']);
    }

    public function test_typescript_generator_outputs_valid_interfaces(): void
    {
        $parser = new RouteParser();
        $routes = $parser->getApiRoutes();
        
        $generator = new TypeScriptGenerator();
        $tsCode = $generator->generate($routes);

        $this->assertStringContainsString('export interface UsersStoreRequest {', $tsCode);
        $this->assertStringContainsString('email: string;', $tsCode);
        $this->assertStringContainsString('profile?: {', $tsCode);
        $this->assertStringContainsString('bio?: string;', $tsCode);
    }

    public function test_swift_generator_outputs_valid_structs(): void
    {
        $parser = new RouteParser();
        $routes = $parser->getApiRoutes();

        $generator = new SwiftGenerator();
        $swiftCode = $generator->generate($routes);

        $this->assertStringContainsString('struct UsersStoreRequest: Codable {', $swiftCode);
        $this->assertStringContainsString('let email: String', $swiftCode);
        $this->assertStringContainsString('struct UsersStoreRequestProfile: Codable {', $swiftCode);
    }

    public function test_java_generator_outputs_valid_records(): void
    {
        $parser = new RouteParser();
        $routes = $parser->getApiRoutes();

        $generator = new JavaGenerator();
        $javaCode = $generator->generate($routes);

        $this->assertStringContainsString('public record UsersStoreRequest(', $javaCode);
        $this->assertStringContainsString('@JsonProperty("email") String email', $javaCode);
        $this->assertStringContainsString('public record UsersStoreRequestProfile(', $javaCode);
    }

    public function test_dart_generator_outputs_valid_serializers(): void
    {
        $parser = new RouteParser();
        $routes = $parser->getApiRoutes();

        $generator = new DartGenerator();
        $dartCode = $generator->generate($routes);

        $this->assertStringContainsString('class UsersStoreRequest {', $dartCode);
        $this->assertStringContainsString('final String email;', $dartCode);
        $this->assertStringContainsString('factory UsersStoreRequest.fromJson(Map<String, dynamic> json)', $dartCode);
    }

    public function test_go_generator_outputs_valid_json_tags(): void
    {
        $parser = new RouteParser();
        $routes = $parser->getApiRoutes();

        $generator = new GoGenerator();
        $goCode = $generator->generate($routes);

        $this->assertStringContainsString('type UsersStoreRequest struct {', $goCode);
        $this->assertStringContainsString('Email string `json:"email"`', $goCode);
        $this->assertStringContainsString('Profile *UsersStoreRequestProfile `json:"profile,omitempty"`', $goCode);
    }

    public function test_openapi_generator_compiles_spec(): void
    {
        $parser = new RouteParser();
        $routes = $parser->getApiRoutes();

        $generator = new OpenApiSpecGenerator();
        $spec = $generator->generate($routes);

        $this->assertEquals('3.1.0', $spec['openapi']);
        $this->assertArrayHasKey('/api/users/store', $spec['paths']);
        $this->assertArrayHasKey('post', $spec['paths']['/api/users/store']);
    }

    public function test_postman_generator_creates_collection(): void
    {
        $parser = new RouteParser();
        $routes = $parser->getApiRoutes();

        $generator = new PostmanGenerator();
        $collectionJson = $generator->generate($routes);
        $collection = json_decode($collectionJson, true);

        $this->assertArrayHasKey('info', $collection);
        $this->assertArrayHasKey('item', $collection);
        $this->assertEquals('users.store', $collection['item'][0]['name']);
    }
}

// --- MOCK DECLARATIONS FOR THE TEST SUITE ---

class MockUserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'profile' => 'array',
            'profile.bio' => 'string|max:1000',
            'profile.age' => 'integer',
        ];
    }
}

class MockUserController
{
    public function store(MockUserStoreRequest $request)
    {
        return Response::json(['success' => true]);
    }

    public function index()
    {
        return Response::json([]);
    }
}
