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
class PackageController extends Controller
{
    public function myPackages(Request $request, ProviderAuth $authService, CryptoService $crypto)
    {
        $activeUser = $authService->getActiveUser(); // ini akan auto-refresh token jika expired
        if (!$activeUser) return redirect()->route('login');
        $activePackages = [];
        $error = null;
        try {
            $idToken = $activeUser['tokens']['id_token'] ?? '';
            // Jika id_token masih kosong setelah refresh, beri pesan
            if (empty($idToken)) {
                return Inertia::render('Packages/My', [
                    'quotas'  => [],
                    'error'   => 'Token tidak valid. Silakan login ulang.',
                    'profile' => [
                        'number' => $activeUser['number'] ?? '',
                        'type'   => $activeUser['subscription_type'] ?? '',
                    ]
                ]);
            }
            $apiKey = $crypto->getApiKey();
            $path = 'api/v8/packages/quota-details';
            $payload = [
                "is_enterprise" => false,
                "lang" => "en",
                "family_member_id" => ""
            ];
            $encrypted = $crypto->encryptSignXData('POST', $path, $idToken, $payload);
            $now = Carbon::now('Asia/Jakarta');
            $baseUrl = env('PROVIDER_BASE_URL', 'https://api.myxl.xlaxiata.co.id');
            $centiseconds = str_pad(floor($now->micro / 10000), 2, '0', STR_PAD_LEFT);
            $xRequestAt = $now->format('Y-m-d\TH:i:s.') . $centiseconds . $now->format('P');
            $xSignatureTime = (string) (int) ($encrypted['encrypted_body']['xtime'] / 1000);
            $headers = [
                'host'              => parse_url($baseUrl, PHP_URL_HOST),
                'content-type'      => 'application/json; charset=utf-8',
                'user-agent'        => env('PROVIDER_UA'),
                'x-api-key'         => $apiKey,
                'authorization'     => 'Bearer ' . $idToken,
                'x-hv'              => 'v3',
                'x-signature-time'  => $xSignatureTime,
                'x-signature'       => $encrypted['x_signature'],
                'x-request-id'      => (string) Str::uuid(),
                'x-request-at'      => $xRequestAt,
                'x-version-app'     => '8.9.0',
            ];
            $providerRes = Http::withHeaders($headers)
                ->timeout(30)
                ->post($baseUrl . '/' . $path, $encrypted['encrypted_body']);
            $resData = $providerRes->json();
            if (isset($resData['xdata']) || isset($resData['encrypted_body'])) {
                $decryptedData = $crypto->decryptXData($resData);
                $decryptedJson = is_string($decryptedData) ? json_decode($decryptedData, true) : $decryptedData;
                if (isset($decryptedJson['status']) && $decryptedJson['status'] === 'SUCCESS') {
                    $activePackages = $decryptedJson['data']['quotas'] ?? [];
                } else {
                    $error = "API Error: " . ($decryptedJson['message'] ?? 'Unknown Error');
                }
            } else {
                $error = "API Provider menolak request: " . $providerRes->body();
                Log::error("MyPackages Provider API Response: " . $providerRes->body());
            }
        } catch (\Exception $e) {
            $error = "Sistem Error: " . $e->getMessage();
            Log::error("MyPackages Exception: " . $e->getMessage());
        }
        return Inertia::render('Packages/My', [
            'quotas'  => $activePackages,
            'error'   => $error,
            'profile' => [
                'number' => $activeUser['number'] ?? '',
                'type'   => $activeUser['subscription_type'] ?? '',
            ]
        ]);
    }
}
