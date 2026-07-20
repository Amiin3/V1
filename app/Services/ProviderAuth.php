<?php
namespace App\Services;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProviderAuth
{
    public function loginAndSaveUser($msisdn, $tokens, $role = 'admin')
    {
        Log::info('📥 loginAndSaveUser dipanggil', ['msisdn' => $msisdn, 'role' => $role, 'has_id_token' => !empty($tokens['id_token'])]);

        $activeUser = [
            'number' => $msisdn,
            'subscription_type' => 'PREPAID',
            'tokens' => $tokens,
            'role' => $role,
            'last_refresh_time' => time(),
            'is_admin_token' => ($role === 'admin' && ($tokens['id_token'] ?? '') === 'admin_token')
        ];
        Session::put('active_user', $activeUser);
        Session::save();

        Log::info('✅ Session tersimpan', ['number' => $msisdn]);

        $saved = $this->saveToDatabase($msisdn, $tokens);
        Log::info('💾 Database: ' . ($saved ? 'BERHASIL' : 'GAGAL'), ['msisdn' => $msisdn]);
    }

    public function getActiveUser()
    {
        $user = Session::get('active_user');
        if (!$user) return null;

        if (!empty($user['is_admin_token'])) {
            return $user;
        }

        if ((time() - $user['last_refresh_time']) > 300) {
            $refreshToken = $user['tokens']['refresh_token'] ?? '';
            if (!empty($refreshToken)) {
                $tokens = $this->getNewToken($refreshToken);
                if ($tokens) {
                    $user['tokens'] = $tokens;
                    $user['last_refresh_time'] = time();
                    Session::put('active_user', $user);
                    Session::save();
                    $this->saveToDatabase($user['number'], $tokens);
                }
            }
        }

        return $user;
    }

    public function logout()
    {
        Session::forget('active_user');
        Session::save();
    }

    public function getNewToken($refreshToken)
    {
        $crypto = app(\App\Services\CryptoService::class);
        $baseCiamUrl = config('provider.base_ciam_url');
        $url = $baseCiamUrl . '/realms/xl-ciam/protocol/openid-connect/token';
        $headers = [
            'Host' => parse_url($baseCiamUrl, PHP_URL_HOST),
            'ax-request-at' => $crypto->javaLikeTimestamp(now()),
            'ax-device-id' => $crypto->axDeviceId(),
            'ax-request-id' => (string) Str::uuid(),
            'ax-request-device' => 'samsung',
            'ax-request-device-model' => 'SM-N935F',
            'ax-fingerprint' => $crypto->loadAxFp(),
            'authorization' => 'Basic ' . config('provider.basic_auth'),
            'user-agent' => config('provider.user_agent'),
            'ax-substype' => 'PREPAID',
            'content-type' => 'application/x-www-form-urlencoded',
        ];
        try {
            $response = Http::withHeaders($headers)->asForm()->timeout(30)->post($url, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);
            $body = $response->json();
            if ($response->failed() || empty($body['id_token'])) {
                Log::error('Gagal refresh token', ['status' => $response->status(), 'body' => $body]);
                return null;
            }
            return [
                'access_token' => $body['access_token'],
                'id_token' => $body['id_token'],
                'refresh_token' => $body['refresh_token'] ?? $refreshToken,
            ];
        } catch (\Exception $e) {
            Log::error('Exception refresh token: ' . $e->getMessage());
            return null;
        }
    }

    private function saveToDatabase($msisdn, $tokens)
    {
        if (!empty($tokens['refresh_token'])) {
            $sessionData = json_encode($tokens);
            if (Schema::hasTable('reseller_sessions')) {
                DB::table('reseller_sessions')->updateOrInsert(
                    ['phone_number' => $msisdn],
                    ['session_data' => $sessionData, 'is_ready' => 1, 'updated_at' => now()]
                );
                return true;
            }
        }
        return false;
    }
}
