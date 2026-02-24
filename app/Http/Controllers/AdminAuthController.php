<?php

namespace App\Http\Controllers;

use App\Models\AdminLogin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
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

        if (!$admin) {
            return back()->withInput()->with('error', 'Invalid username or password.');
        }

        $passwordOk = Hash::check($credentials['password'], $admin->admin_password)
            || $credentials['password'] === $admin->admin_password;

        if (!$passwordOk) {
            return back()->withInput()->with('error', 'Invalid username or password.');
        }

        if ($admin->cur_status !== 'ACTIVE') {
            return back()->withInput()->with('error', 'Your account is inactive.');
        }

        session([
            'admin_id'        => $admin->id,
            'admin_username'  => $admin->admin_username,
            'admin_user_type' => $admin->user_type,
        ]);

        $admin->active_session_id = session()->getId();
        $admin->last_login_at = Carbon::now();
        $admin->save();

        return redirect()->route('admin.users.index');
    }

    public function logout()
    {
        $adminId = session('admin_id');

        if ($adminId) {
            AdminLogin::where('id', $adminId)->update([
                'active_session_id' => null,
            ]);
        }

        session()->forget(['admin_id', 'admin_username', 'admin_user_type']);
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
