<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SsoTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SsoCallbackController extends Controller
{
    public function __invoke(Request $request, SsoTokenService $service): RedirectResponse
    {
        $state = $request->query('state');
        $token = $request->query('sso_token');
        $expectedState = $request->session()->pull('sso_state');

        if (! is_string($state) || ! is_string($token) || $token === ''
            || ! is_string($expectedState) || ! hash_equals($expectedState, $state)) {
            return redirect()->route('login')->with('error', 'Sesi login SSO tidak valid atau kedaluwarsa. Silakan coba lagi.');
        }

        try {
            $profile = $service->verify($token);
            $user = DB::transaction(fn () => $this->resolveLocalUser($profile));

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('sso_authenticated', true);

            return redirect()->to(config('services.sso.after_login_url', '/'))
                ->withHeaders(['Referrer-Policy' => 'no-referrer']);
        } catch (Throwable $exception) {
            report($exception);
            // Never put the SSO token, raw identity payload or upstream exceptions in a browser response.
            return redirect()->route('login')
                ->with('error', 'Login SSO belum berhasil. Periksa akun atau hubungi administrator SIGIT.');
        }
    }

    private function resolveLocalUser(array $profile): User
    {
        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        $ssoId = trim((string) ($profile['sso_id'] ?? $profile['sub'] ?? ''));
        $employeeId = trim((string) ($profile['employee_id'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('NEXID tidak menyediakan email yang valid.');
        }
        if (array_key_exists('email_verified', $profile) && $profile['email_verified'] === false) {
            throw new RuntimeException('Email NEXID belum terverifikasi.');
        }
        if ($ssoId === '' && $employeeId === '') {
            throw new RuntimeException('NEXID tidak menyediakan pengenal pengguna tetap.');
        }

        $matched = collect();
        if ($ssoId !== '') {
            $match = User::where('sso_id', $ssoId)->first();
            if ($match) $matched->push($match);
        }
        if ($employeeId !== '') {
            $match = User::where('employee_id', $employeeId)->first();
            if ($match) $matched->push($match);
        }
        $match = User::where('email', $email)->first();
        if ($match) $matched->push($match);

        if ($matched->unique('id')->count() > 1) {
            throw new RuntimeException('Identitas NEXID terhubung ke lebih dari satu akun SIGIT.');
        }
        $user = $matched->first() ?? new User();

        if ($user->exists && (
            ($user->sso_id && $ssoId && $user->sso_id !== $ssoId) ||
            ($user->employee_id && $employeeId && $user->employee_id !== $employeeId)
        )) throw new RuntimeException('Identitas NEXID tidak cocok dengan akun lokal.');

        $user->name = trim((string) ($profile['name'] ?? '')) ?: ($user->name ?: 'Pengguna NEXID');
        $user->email = $email;
        if ($ssoId !== '') $user->sso_id = $ssoId;
        if ($employeeId !== '') $user->employee_id = $employeeId;
        if (! $user->exists) $user->password = Str::random(64);
        $user->save();

        // Intentionally do not accept external 'roles' as SIGIT permissions.
        // An administrator grants access in /admin/users.
        return $user;
    }
}
