<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SsoRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $baseUrl = rtrim((string) config('services.sso.base_url'), '/');
        $clientId = (string) config('services.sso.client_id');
        $callback = (string) config('services.sso.callback_url');

        if (! str_starts_with($baseUrl, 'https://') || $clientId === '' || ! filter_var($callback, FILTER_VALIDATE_URL)) {
            return redirect()->route('login')->with('error', 'Konfigurasi SSO belum lengkap.');
        }

        $state = Str::random(48);
        $request->session()->put('sso_state', $state);

        $url = $baseUrl.'/sso/authorize?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $callback,
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away($url)->withHeaders(['Referrer-Policy' => 'no-referrer']);
    }
}
