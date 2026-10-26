<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SsoTokenService
{
    public function verify(string $token): array
    {
        $baseUrl = rtrim((string) config('services.sso.base_url'), '/');
        if (! str_starts_with($baseUrl, 'https://') || ! config('services.sso.client_id') || ! config('services.sso.client_secret')) {
            throw new RuntimeException('Konfigurasi verifikasi SSO belum lengkap.');
        }

        $response = Http::asForm()->acceptJson()->connectTimeout(5)->timeout(12)
            ->post($baseUrl.'/api/sso/verify-token', [
                'client_id' => config('services.sso.client_id'),
                'client_secret' => config('services.sso.client_secret'),
                'sso_token' => $token,
            ]);

        if (! $response->successful()) throw new RuntimeException('Verifikasi token ditolak oleh NEXID.');

        $user = $response->json('user');
        if (! is_array($user)) throw new RuntimeException('Format profil pengguna NEXID tidak sesuai.');

        return $user;
    }
}
