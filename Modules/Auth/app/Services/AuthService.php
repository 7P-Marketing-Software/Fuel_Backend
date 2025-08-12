<?php

namespace Modules\Auth\Services;

use App\Http\Traits\ResponsesTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;

class AuthService
{
    use ResponsesTrait;
    private User $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    public function register($request)
    {
        if ($request->file('profile_image')) {
            $file = $request->file('profile_image');
            $fileName = 'Profile_Image' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('Profile_Images', $fileName, 'public');
            $fullPath = Storage::url($path);
        }

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email', null),
            'country_code' => $request->input('country_code', '+2'),
            'phone' => $request->input('phone', null),
            'password' => Hash::make($request->input('password')),
            'profile_image' => isset($fullPath) ? $fullPath : null,
        ]);

        $token = $user->createToken('User Access Token')->plainTextToken;

        $UserRole = Role::where('name', 'User')->first();
        $user->assignRole($UserRole);
        unset($user->roles);

        return [
            'token' => $token,
            'user' => $user,
            'role' => $user->roles->first()->name ?? null
        ];
    }

    public function login($request)
    {
        $user = User::where(function ($query) use ($request) {
            if ($request->filled('email')) {
                $query->where('email', $request->input('email'));
            } elseif ($request->filled('phone')) {
                $query->where('phone', $request->input('phone'));
            }
        })->first();

        if(!$user ) {
            return null;
        }
        

        if (!Hash::check($request->input('password'), $user->password)) {
                return null;
        }

        $token = $user->createToken('Access Token')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
            'role' => $user->getRoleNames()[0],
        ];
    }

    public function logout($user)
    {
        $user->currentAccessToken()->delete();
        $user->tokens()->delete();
    }

    public function createForgetPasswordToken($user)
    {
        $token = $user->createToken('Forget password token');
        $token->accessToken->save();
        return $token->plainTextToken;
    }

    public function resetPassword($user, $password)
    {
        $user->update([
            'password' => Hash::make($password),
            'otp' => null,
        ]);
        $user->tokens()->delete();
    }
}
