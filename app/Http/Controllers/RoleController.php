<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::with('permissions')->withCount(['permissions', 'users'])->get();
        $permissions = Permission::all();
        $totalRoles = Role::count();
        $totalPermissions = Permission::count();
        $redirectOptions = $this->redirectOptions();

        return view('roles.index', compact('roles', 'permissions', 'totalRoles', 'totalPermissions', 'redirectOptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $allowedRedirectRoutes = array_keys($this->redirectOptions());

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'default_redirect_route' => [
                'nullable',
                'string',
                'max:255',
                Rule::in($allowedRedirectRoutes),
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'default_redirect_route' => $validated['default_redirect_route'] ?? null,
        ]);

        // Store description in a custom column if you have it in the roles table
        // Otherwise, you might need to add a migration to add description column

        if (isset($validated['permissions'])) {
            $role->syncPermissions(Permission::findMany($validated['permissions']));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil ditambahkan!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $allowedRedirectRoutes = array_keys($this->redirectOptions());

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'description' => 'nullable|string',
            'default_redirect_route' => [
                'nullable',
                'string',
                'max:255',
                Rule::in($allowedRedirectRoutes),
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $validated['name'],
            'default_redirect_route' => $validated['default_redirect_route'] ?? null,
        ]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions(Permission::findMany($validated['permissions']));
        } else {
            $role->syncPermissions([]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil diupdate!');
    }

    private function redirectOptions(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(function ($route): bool {
                $name = $route->getName();
                $uri = (string) $route->uri();

                if (blank($name)) {
                    return false;
                }

                if (! collect($route->methods())->contains('GET')) {
                    return false;
                }

                if (str_contains($uri, '{')) {
                    return false;
                }

                return str_starts_with($name, 'admin.')
                    || str_starts_with($name, 'waiter.')
                    || $name === 'profile.edit';
            })
            ->sortBy(fn ($route) => $route->getName())
            ->mapWithKeys(fn ($route): array => [
                $route->getName() => $this->formatRedirectLabel($route->getName()),
            ])
            ->all();
    }

    private function formatRedirectLabel(string $routeName): string
    {
        if ($routeName === 'profile.edit') {
            return 'Profile';
        }

        $segments = explode('.', $routeName);
        $scope = array_shift($segments);

        $scopeLabel = match ($scope) {
            'admin' => 'Admin',
            'waiter' => 'Waiter',
            default => ucfirst((string) $scope),
        };

        $pageLabel = collect($segments)
            ->reject(fn (string $segment): bool => in_array($segment, ['index'], true))
            ->map(function (string $segment): string {
                return collect(explode('-', $segment))
                    ->map(fn (string $word): string => ucfirst($word))
                    ->implode(' ');
            })
            ->filter()
            ->implode(' - ');

        if ($pageLabel === '') {
            $pageLabel = 'Dashboard';
        }

        return $scopeLabel.' · '.$pageLabel;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting roles that have users
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')->with('error', 'Role tidak bisa dihapus karena masih memiliki user!');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil dihapus!');
    }
}
