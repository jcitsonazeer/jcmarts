<?php

namespace App\Http\Middleware;

use App\Models\AdminLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * How long (in seconds) an admin record is cached between requests.
     */
    public const CACHE_TTL = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $adminId = $request->session()->get('admin_id');

        if (!$adminId) {
            return redirect()->route('admin.login');
        }

        $cacheKey = 'admin_login:' . $adminId;

        $admin = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($adminId) {
            return AdminLogin::find($adminId);
        });

        // Admin no longer exists or has been deactivated: kill the session.
        if (!$admin || $admin->cur_status !== 'ACTIVE') {
            Cache::forget($cacheKey);

            $request->session()->forget(['admin_id', 'admin_username', 'admin_user_type', 'active_session_id']);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('error', 'Your session is no longer valid. Please login again.');
        }

        // Single active session enforcement: if another login rotated the
        // active_session_id, this session must be invalidated.
        if ($admin->active_session_id !== $request->session()->getId()) {
            Cache::forget($cacheKey);

            $request->session()->forget(['admin_id', 'admin_username', 'admin_user_type', 'active_session_id']);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('error', 'Your account was signed in elsewhere. Please login again.');
        }

        return $next($request);
    }
}