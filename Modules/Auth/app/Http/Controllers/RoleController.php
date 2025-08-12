<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Controller;
use Modules\Auth\Models\User;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return $this->respondOk($roles, 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        return $this->respondCreated($role, 'Role created successfully');
    }

    /**
     * Display the specified role with its permissions.
     */
    public function show($id)
    {
        $role = Role::with('permissions')->find($id);
        return $role ? $this->respondOk($role, 'Role retrieved successfully') : $this->respondNotFound('Role not found');
    }

    /**
     * Update the specified role and permissions.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
            'permissions' => 'array|exists:permissions,name'
        ]);

        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->respondNotFound(null,'Role not found');
        }

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return $this->respondOk($role, 'Role updated successfully');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->respondNotFound(null,'Role not found');
        }


        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        return $this->respondOk(null, 'Role deleted successfully');
    }


    /**
     * Get all permissions
     */
    public function getPermissions()
    {
        $permissions = Permission::all();

        return $this->respondOk($permissions, 'Permissions retrieved successfully');
    }

    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions',
        ]);

        $permission = Permission::firstOrCreate([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        return $this->respondCreated($permission);
    }

    /**
     * Assign permissions to a specific role.
     */
    public function assignPermissions(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'required|array|exists:permissions,name'
        ]);

        $role = Role::find($id);

        if (!$role) {
            return $this->respondNotFound(null,'Role not found');
        }

        $role->syncPermissions($request->permissions);

        return $this->respondOk($role, 'Permissions assigned successfully');
    }

    public function getUserPermissions($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return $this->respondNotFound(null, 'User admin not found');
        }

        $permissions = [
            'direct_permissions' => $user->getDirectPermissions(),
            'role_permissions' => $user->getPermissionsViaRoles(),
            'all_permissions' =>$user->getAllPermissions(),
        ];

        return $this->respondOk($permissions, 'User permissions retrieved successfully');
    }

    public function assignPermissionToUser(Request $request, $userId)
    {
        $request->validate([
            'permissions' => 'required|array|exists:permissions,name'
        ]);

        $user = User::find($userId);

        if (!$user) {
            return $this->respondNotFound(null, 'User admin not found');
        }

        $user->givePermissionTo($request->permissions);
        $user->load('permissions');

        return $this->respondOk($user, 'Permissions assigned to user successfully');
    }

}
