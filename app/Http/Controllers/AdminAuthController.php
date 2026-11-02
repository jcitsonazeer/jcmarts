<?php

namespace App\Http\Controllers;

use App\Models\AdminLogin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AdminAuthController extends Controller
{
    public const MAX_LOGIN_ATTEMPTS = 5;
    public const LOGIN_LOCKOUT_SECONDS = 900;

    public function showLogin()
    {
        if (session()->has('admin_id')) {
            return redirect()->route('admin.users.index');
        }

        return view('admin.dashboard.loginpage');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = strtoupper($credentials['username']);
        $admin = AdminLogin::where('admin_username', $username)->first();

        $lockKey = 'admin-login:' . $username;

        if ($admin && RateLimiter::tooManyAttempts($lockKey, self::MAX_LOGIN_ATTEMPTS)) {
            $retryAfter = RateLimiter::availableIn($lockKey);

            return response(
                'Too many failed login attempts. Please try again in ' . $retryAfter . ' seconds.',
                429
            );
        }

        if (!$admin) {
            return back()->withInput()->with('error', 'Invalid username or password.');
        }

        $passwordOk = Hash::check($credentials['password'], $admin->admin_password);

        if (!$passwordOk) {
            RateLimiter::hit($lockKey, self::LOGIN_LOCKOUT_SECONDS);

            if (RateLimiter::tooManyAttempts($lockKey, self::MAX_LOGIN_ATTEMPTS)) {
                return response(
                    'Too many failed login attempts. Please try again later.',
                    429
                );
            }

            return back()->withInput()->with('error', 'Invalid username or password.');
        }

        if ($admin->cur_status !== 'ACTIVE') {
            RateLimiter::hit($lockKey, self::LOGIN_LOCKOUT_SECONDS);

            return back()->withInput()->with('error', 'Your account is inactive.');
        }

        RateLimiter::clear($lockKey);

        $request->session()->regenerate();

        session([
            'admin_id'          => $admin->id,
            'admin_username'    => $admin->admin_username,
            'admin_user_type'   => $admin->user_type,
            'active_session_id' => $request->session()->getId(),
        ]);

        $admin->active_session_id = $request->session()->getId();
        $admin->last_login_at = Carbon::now();
        $admin->save();

        Cache::forget('admin_login:' . $admin->id);

        return redirect()->route('admin.users.index');
    }

    public function logout()
    {
        $adminId = session('admin_id');

        if ($adminId) {
            AdminLogin::where('id', $adminId)->update([
                'active_session_id' => null,
            ]);

            Cache::forget('admin_login:' . $adminId);
        }

        session()->forget(['admin_id', 'admin_username', 'admin_user_type', 'active_session_id']);
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}