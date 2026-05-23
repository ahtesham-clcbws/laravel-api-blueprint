# Active Backlog & Sprints

This backlog tracks the sprints and tasks for developing, reviewing, and improving **Laravel API Blueprint**.

## Sprint 0: Code Review & Validation (Completed)
- [x] Initial workspace governance setup (create `.agents/` directory structure)
- [x] Review the proposed code blueprint for potential security, correctness, and architectural issues
- [x] Resolve FormRequest reflection and dynamic instantiation issues (container context mocking)
- [x] Handle validation rulesets containing closures, Rules, or complex objects
- [x] Build dot-notation rule mapping recursively converting flat keys to nested objects/arrays tree
- [x] Implement 5 technology structural code generators: TypeScript, Swift, Java (Records), Dart (Flutter serializer), Go
- [x] Formulate compliant OpenAPI 3.1.0 and Postman v2.1.0 collections (with path parameters as variables)
- [x] Add high-performance file-based caching to prevent controller reflection overhead in production
- [x] Enhance web dashboard viewer using Stoplight Elements and premium sliding Glassmorphism code exporter drawer
- [x] Create comprehensive PHPUnit feature test suite and verify 100% test success
- [x] Design custom shields.io logo badge and integrate it across both README.md and dashboard layout
- [x] Document installation, configuration, CLI/UI usage, and architecture inside a beautiful, detailed README.md
- [x] Create Laravel skeleton project inside `demo_project/` using SQLite and latest Laravel v13.x
- [x] Configure and install package locally using symlinks and publish package configs
- [x] Scaffold realistic complex API routes, controllers, and FormRequest classes containing nested lists and objects
- [x] Run Artisan export command verifying 100% accuracy of generated output files
- [x] Boot up Laravel development server for live user verification

## Sprint 1: Future Enhancements (Backlog)
- [ ] Add support for custom response templates parser (automatically mapping controller response payloads)
- [ ] Add command parameter flags to export selective schemas only (e.g. `--types=ts,go`)
- [ ] Support local offline asset delivery for dashboard stylesheets
