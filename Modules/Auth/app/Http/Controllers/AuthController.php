<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\ForgetUserRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\ResetUserRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AuthService;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Services\WhatsAppService;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService,protected WhatsAppService $whatsAppService)
    {
        $this->authService = $authService;
        $this->whatsAppService = $whatsAppService;
    }
    public function register(RegisterRequest $request)
    {

        $user = $this->authService->register($request);

        if (!$user) {
            return $this->respondNotFound(null,'User registration failed');
        }

        return $this->respondCreated($user, 'User registered successfully');
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request);

        if (!$result) {
            return $this->respondNotFound(null,'Invalid credentials');
        }

        return $this->respondOk($result, 'Logged in successfully');
    }

    public function logout()
    {
        $user = auth()->user();

        if ($user->currentAccessToken()) {
            $this->authService->logout($user);
        } else {
            return $this->respondNotFound(null, 'No active session found.');
        }
        return $this->respondOk(null, 'Logged out successfully from the device.');
    }

    public function forgetPassword(ForgetUserRequest $request)
    {
        $user = User::where('phone', $request->validated()['phone'])->first();

        if ($this->sendWhatsAppOtp($user)) {
            return $this->respondOk(null, 'Please check your phone');
        } else {
            return $this->respondNotFound(null, 'Something went wrong please try again later');
        }
    }

    public function checkPhoneOTPForgetPassword(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string','exists:users,phone'],
            'phoneOtp' => 'required|digits:5',
        ]);

        $user = User::where('phone', $validated['phone'])->first();

        if (!$user) {
            return $this->respondNotFound(null, 'Phone number not found.');
        }

        $maxAttempts = 5;
        $lockDuration = 5;

        if ($user->otp_sent_at < now()->subMinutes($lockDuration)) {
            $user->update(['otp_attempts' => 0]);
        }

        if ($user->otp_attempts >= $maxAttempts) {
            return $this->respondNotFound(null, 'Maximum OTP attempts exceeded. Please try again after 5 minutes.');
        }

        if ($user->otp_expires_at < now()) {
            return $this->respondNotFound(null, 'OTP has expired. Please request a new one.');
        }

        if ($validated['phoneOtp'] != $user->otp) {
            $user->increment('otp_attempts');
            $user->update(['otp_sent_at' => now()]);
            return $this->respondNotFound(null, 'Invalid OTP');
        }

        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
            'otp_verified_at' => now(),
        ]);
        $user->save();

        $user->tokens()->delete();
        $data = [
            'token' => $this->authService->createForgetPasswordToken($user),
        ];

            return $this->respondOk($data, 'OTP verified successfully');
    }

    public function resetPassword(ResetUserRequest $request)
    {
        $fields = $request->validated();

        $user = auth('sanctum')->user();

        $this->authService->resetPassword($user, $fields['password']);

        return $this->respondOk(null, 'Password reset successfully');
    }

    public function sendWhatsAppOtp($user)
    {
        $otp = rand(10000, 99999);
        $country_code = $user->country_code ?? '+2';
        $this->whatsAppService->sendText($country_code . $user->phone, $otp);
        $user->update([
            'otp' => $otp,
            'otp_sent_at' => now(),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts' => $user->otp_attempts + 1,
        ]);
        $user->save();
        return true;
    }

    public function  resendOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|numeric|exists:users,phone',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->respondNotFound(null, 'Phone not found');
        }

        $user = User::where('phone', $validated['phone'])->first();

        $maxAttempts = 5;
        $lockDuration = 5;

        if ($user->otp_sent_at < now()->subMinutes($lockDuration)) {
            $user->update(['otp_attempts' => 0]);
        }

        if ($user->otp_attempts >= $maxAttempts) {
            return $this->respondNotFound(null, 'Maximum OTP attempts exceeded. Please try again after 5 minutes.');
        }

        if ($this->sendWhatsAppOtp($user)) {
            return $this->respondOk(null, 'Please check your phone.');
        }

        return $this->respondNotFound(null, 'Something went wrong please try again later');
    }
}
