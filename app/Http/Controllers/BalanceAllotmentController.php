<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProviderAuth;
use App\Services\CryptoService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BalanceAllotmentController extends Controller
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
        if (!preg_match('#^https?://#', $baseUrl)) {
            throw new \Exception("PROVIDER_BASE_URL tidak valid: $baseUrl");
        }

        $centiseconds = str_pad(floor($now->micro / 10000), 2, '0', STR_PAD_LEFT);
        $xRequestAt = $now->format('Y-m-d\TH:i:s.') . $centiseconds . $now->format('P');
        $xSignatureTime = (string) (int) ($encrypted['encrypted_body']['xtime'] / 1000);

        $headers = [
            'host'              => parse_url($baseUrl, PHP_URL_HOST),
            'content-type'      => 'application/json; charset=utf-8',
            'user-agent'        => config('provider.user_agent') ?? env('PROVIDER_UA', ''),
            'x-api-key'         => $apiKey,
            'authorization'     => 'Bearer ' . $idToken,
            'x-hv'              => 'v3',
            'x-signature-time'  => $xSignatureTime,
            'x-signature'       => $encrypted['x_signature'],
            'x-request-id'      => (string) Str::uuid(),
            'x-request-at'      => $xRequestAt,
            'x-version-app'     => '8.9.0',
        ];

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post($baseUrl . '/' . $path, $encrypted['encrypted_body']);

        $decrypted = $this->crypto->decryptXData($response->json());
        return is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
    }

    // GET /balance-allotment
    public function index()
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $idToken = $activeUser['tokens']['id_token'];
        $balance = 0;

        try {
            $path = "api/v8/packages/balance-and-credit";
            $payload = ["is_enterprise" => false, "lang" => "en"];
            $res = $this->sendRequest($path, $payload, $idToken);
            $balance = (int) ($res['data']['balance']['remaining'] ?? 0);
        } catch (\Exception $e) {
            Log::error('Balance allotment index: ' . $e->getMessage());
        }

        return Inertia::render('BalanceAllotment/Index', [
            'balance' => $balance,
            'profile' => [
                'number' => $activeUser['number'] ?? '',
            ]
        ]);
    }

    // POST /balance-allotment/transfer
    public function transfer(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'pin'      => 'required|string|size:6',
            'receiver' => 'required|string|starts_with:628',
            'amount'   => 'required|integer|min:5000',
        ]);

        try {
            $apiKey = $this->crypto->getApiKey();
            $tokens = $activeUser['tokens'];
            $idToken = $tokens['id_token'];
            $accessToken = $tokens['access_token'];
            $myNumber = $activeUser['number'];

            // 1. Dapatkan auth_code (stage_token) dari CIAM
            $authCode = $this->getAuthCode($tokens, $request->pin, $myNumber);
            if (!$authCode) {
                return response()->json(['error' => 'Gagal verifikasi PIN. Pastikan PIN benar.'], 400);
            }

            // 2. Dapatkan x-signature khusus untuk balance allotment
            $path = "sharings/api/v8/balance/allotment";
            $xSignature = $this->crypto->callCryptoApi('/sign-balance-allotment', [
                'access_token' => $accessToken,
                'msisdn'       => $request->receiver,
                'amount'       => (int) $request->amount,
                'path'         => $path,
            ])['x_signature'] ?? null;

            if (!$xSignature) {
                return response()->json(['error' => 'Gagal membuat tanda tangan.'], 500);
            }

            // 3. Enkripsi payload
            $allotmentPayload = [
                "access_token"  => $accessToken,
                "receiver"      => $request->receiver,
                "amount"        => (int) $request->amount,
                "stage_token"   => $authCode,
                "lang"          => "en",
                "is_enterprise" => false,
            ];

            $encrypted = $this->crypto->encryptSignXData('POST', $path, $idToken, $allotmentPayload);
            $xSignatureTime = (string) (int) ($encrypted['encrypted_body']['xtime'] / 1000);

            $now = Carbon::now('Asia/Jakarta');
            $baseUrl = config('provider.base_url') ?: env('PROVIDER_BASE_URL', 'https://api.myxl.xlaxiata.co.id');
            $centiseconds = str_pad(floor($now->micro / 10000), 2, '0', STR_PAD_LEFT);
            $xRequestAt = $now->format('Y-m-d\TH:i:s.') . $centiseconds . $now->format('P');

            // 4. Kirim request dengan x-signature khusus
            $headers = [
                'host'              => parse_url($baseUrl, PHP_URL_HOST),
                'content-type'      => 'application/json; charset=utf-8',
                'user-agent'        => config('provider.user_agent') ?? env('PROVIDER_UA', ''),
                'x-api-key'         => $apiKey,
                'authorization'     => 'Bearer ' . $idToken,
                'x-hv'              => 'v3',
                'x-signature-time'  => $xSignatureTime,
                'x-signature'       => $xSignature,
                'x-request-id'      => (string) Str::uuid(),
                'x-request-at'      => $xRequestAt,
                'x-version-app'     => '8.9.0',
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($baseUrl . '/' . $path, $encrypted['encrypted_body']);

            $decrypted = $this->crypto->decryptXData($response->json());
            $result = is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;

            if (($result['status'] ?? '') === 'SUCCESS') {
                return response()->json([
                    'status'  => 'SUCCESS',
                    'message' => 'Transfer berhasil!',
                    'data'    => $result
                ]);
            } else {
                return response()->json([
                    'error' => $result['message'] ?? 'Transfer gagal.'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Balance allotment: ' . $e->getMessage());
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // Helper: dapatkan auth_code (seperti get_auth_code di ciam.py)
    private function getAuthCode($tokens, $pin, $msisdn)
    {
        try {
            $path = "ciam/auth/authorization-token/generate";
            $baseCiamUrl = config('provider.base_ciam_url');
            $now = Carbon::now('Asia/Jakarta');
            $requestAt = $this->crypto->javaLikeTimestamp($now);
            $requestId = Str::uuid()->toString();

            $response = Http::withHeaders([
                'Host'                     => parse_url($baseCiamUrl, PHP_URL_HOST),
                'Ax-Request-At'            => $requestAt,
                'Ax-Device-Id'             => $this->crypto->axDeviceId(),
                'Ax-Request-Id'            => $requestId,
                'Ax-Request-Device'        => 'samsung',
                'Ax-Request-Device-Model'  => 'SM-N935F',
                'Ax-Fingerprint'           => $this->crypto->loadAxFp(),
                'Authorization'            => 'Bearer ' . $tokens['access_token'],
                'User-Agent'               => config('provider.user_agent') ?? env('PROVIDER_UA', ''),
                'Ax-Substype'              => 'PREPAID',
                'Content-Type'             => 'application/json',
            ])->post($baseCiamUrl . '/' . $path, [
                'pin'              => base64_encode($pin),
                'transaction_type' => 'SHARE_BALANCE',
                'receiver_msisdn'  => $msisdn,
            ]);

            $data = $response->json();
            return $data['data']['authorization_code'] ?? null;
        } catch (\Exception $e) {
            Log::error('getAuthCode: ' . $e->getMessage());
            return null;
        }
    }
}
