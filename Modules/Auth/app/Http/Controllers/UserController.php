<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;

class UserController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $query = User::with(['roles']);

        if ($request->has('name') && $request->name) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($roleQuery) use ($request) {
                $roleQuery->where('name', 'like', '%' . $request->role . '%');
            });
        }

        $users = $query->paginate();

        return $this->respondOk($users, 'Users retrieved successfully');
    }

    public function showProfile()
    {
        $user = User::find(Auth::id());
        return $this->respondOk($user, 'User Profile');
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email',
            'country_code' => 'nullable|string',
            'phone' => ['nullable', 'string','unique:users,phone'],
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        $user = User::find(Auth::id());

        if ($request->file('profile_image')) {
            if($user->profile_image){
                $this->deleteFromSpaces($user->profile_image);
            }
            $file = $request->file('profile_image');
            $fullPath = $this->uploadToSpaces(
                $file,
                'User',
                'profile_images',
                'profile_image_' . time() . '.' . $file->getClientOriginalExtension()
            );
            $user->profile_image = $fullPath;
        }

        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->phone = $request->phone ?? $user->phone;
        $user->country_code = $request->country_code ?? $user->country_code;

        $user->save();
        return $this->respondOk($user, 'Profile updated successfully.');
    }

    public function changePassword(Request $request)
    {
        $userId = Auth::id();
        $user = User::findOrFail($userId);
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->respondOk(null, "user password updated successfully.");
    }

    public function deleteUser()
    {
       $userId = Auth::id();
       $user = User::findOrFail($userId);
       if($user->profile_image){
            $this->deleteFromSpaces($user->profile_image);
        }
       $user->delete();
       return $this->respondOk(null, 'User deleted successfully');
    }
}
