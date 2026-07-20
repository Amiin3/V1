<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\ProviderAuth;
use App\Services\CryptoService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    private $authService;
    private $crypto;

    public function __construct(ProviderAuth $authService, CryptoService $crypto)
    {
        $this->authService = $authService;
        $this->crypto = $crypto;
    }

    private function sendRequest($path, $payload, $idToken)
    {
        $apiKey = $this->crypto->getApiKey();
        $encrypted = $this->crypto->encryptSignXData('POST', $path, $idToken, $payload);
        $now = Carbon::now('Asia/Jakarta');
        $baseUrl = config('provider.base_url') ?: env('PROVIDER_BASE_URL', 'https://api.myxl.xlaxiata.co.id');
        $centiseconds = str_pad(floor($now->micro / 10000), 2, '0', STR_PAD_LEFT);
        $xRequestAt = $now->format('Y-m-d\TH:i:s.') . $centiseconds . $now->format('P');
        $xSignatureTime = (string) (int) ($encrypted['encrypted_body']['xtime'] / 1000);
        $headers = [
            'host' => parse_url($baseUrl, PHP_URL_HOST), 'content-type' => 'application/json; charset=utf-8',
            'user-agent' => config('provider.user_agent') ?? env('PROVIDER_UA', ''),
            'x-api-key' => $apiKey, 'authorization' => 'Bearer ' . $idToken,
            'x-hv' => 'v3', 'x-signature-time' => $xSignatureTime, 'x-signature' => $encrypted['x_signature'],
            'x-request-id' => (string) Str::uuid(), 'x-request-at' => $xRequestAt, 'x-version-app' => '8.9.0',
        ];
        $response = Http::withHeaders($headers)->timeout(30)->post($baseUrl . '/' . $path, $encrypted['encrypted_body']);
        $decrypted = $this->crypto->decryptXData($response->json());
        return is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
    }

    public function index()
    {
        $user = $this->authService->getActiveUser();
        if (!$user) return redirect('/login');

        $balanceRemaining = 0;
        $balanceExpiredAt = '-';
        $points = 0;
        $tier = 0;

        // Jangan panggil API untuk admin dummy
        if (empty($user['is_admin_token'])) {
            $idToken = $user['tokens']['id_token'] ?? '';
            if (!empty($idToken)) {
                try {
                    $path = "api/v8/packages/balance-and-credit";
                    $payload = ["is_enterprise" => false, "lang" => "en"];
                    $res = $this->sendRequest($path, $payload, $idToken);
                    if (isset($res['data']['balance'])) {
                        $balanceRemaining = $res['data']['balance']['remaining'] ?? 0;
                        $balanceExpiredAt = $res['data']['balance']['expired_at'] ?? '-';
                    }
                } catch (\Exception $e) {
                    Log::error('Gagal balance: ' . $e->getMessage());
                }

                if (($user['subscription_type'] ?? '') === 'PREPAID') {
                    try {
                        $pathTier = "gamification/api/v8/loyalties/tiering/info";
                        $payloadTier = ["is_enterprise" => false, "lang" => "en"];
                        $resTier = $this->sendRequest($pathTier, $payloadTier, $idToken);
                        $tierData = $resTier['data'] ?? [];
                        $tier = $tierData['tier'] ?? 0;
                        $points = $tierData['current_point'] ?? 0;
                    } catch (\Exception $e) {
                        Log::error('Gagal tiering: ' . $e->getMessage());
                    }
                }
            }
        }

        return Inertia::render('Dashboard/Index', [
            'profile' => [
                'number'            => $user['number'] ?? '',
                'subscription_type' => $user['subscription_type'] ?? 'PREPAID',
                'balance'           => (int) $balanceRemaining,
                'balance_expired_at'=> $balanceExpiredAt,
                'points'            => $points,
                'tier'              => $tier,
            ]
        ]);
    }
}
