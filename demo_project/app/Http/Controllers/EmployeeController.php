<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelApiBlueprint\Attributes\Group;

#[Group('Employee Core Management')]
class EmployeeController extends Controller
{
    /**
     * List all employees.
     * Retrieve a filtered list of all employees in the organization.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|min:2',
            'department' => 'nullable|in:Engineering,Marketing,Sales,HR,Finance',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        return response()->json([
            'message' => 'Employees listed successfully.',
            'employees' => [],
            'count' => 0,
        ]);
    }

    /**
     * Register a new employee.
     * Creates a new employee record along with contact and skills schemas.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Employee registered successfully.',
            'employee' => [
                'id' => 42,
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'department' => $request->input('department'),
                'salary' => $request->input('salary'),
                'contact_info' => $request->input('contact_info'),
                'skills' => $request->input('skills'),
            ],
        ], 201);
    }

    /**
     * Retrieve employee details.
     * Get comprehensive details of a single employee.
     *
     * @group Employee Archive
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'employee' => [
                'id' => $id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@company.com',
                'department' => 'Engineering',
            ],
        ]);
    }

    /**
     * Update employee record.
     * Modify details of an existing employee in the system.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'department' => 'required|string|in:Engineering,Marketing,Sales,HR,Finance',
            'salary' => 'nullable|numeric|min:1000',
        ]);

        return response()->json([
            'message' => 'Employee updated successfully.',
            'updated' => true,
        ]);
    }

    /**
     * Remove employee from records.
     * Permanently deletes the employee and their historical records.
     *
     * @tags Employee Archive, Admin Operations
     */
    public function destroy(int $id): JsonResponse
    {
        return response()->json([
            'message' => 'Employee deleted successfully.',
            'deleted' => true,
        ]);
    }
}
