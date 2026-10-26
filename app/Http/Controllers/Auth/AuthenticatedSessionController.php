<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $cameFromSso = (bool) $request->session()->get('sso_authenticated');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($cameFromSso && config('services.sso.logout_url') && config('services.sso.client_id')) {
            $logoutUrl = (string) config('services.sso.logout_url');
            $logoutUrl .= (str_contains($logoutUrl, '?') ? '&' : '?').http_build_query([
                'client_id' => config('services.sso.client_id'),
                'redirect_uri' => config('services.sso.after_logout_url', route('login')),
            ], '', '&', PHP_QUERY_RFC3986);
            return redirect()->away($logoutUrl);
        }
        return redirect()->route('login');
    }
}
