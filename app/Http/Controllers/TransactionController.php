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

class TransactionController extends Controller
{
    public function index(Request $request, ProviderAuth $authService, CryptoService $crypto)
    {
        $activeUser = $authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $transactions = [];
        $error = null;

        try {
            $idToken = $activeUser['tokens']['id_token'] ?? '';
            $apiKey = $crypto->getApiKey();
            $path = 'payments/api/v8/transaction-history';
            $payload = [
                "is_enterprise" => false,
                "lang" => "en"
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

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($baseUrl . '/' . $path, $encrypted['encrypted_body']);

            $resData = $response->json();

            $decrypted = $crypto->decryptXData($resData);
            $decoded = is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;

            if (isset($decoded['status']) && $decoded['status'] === 'SUCCESS') {
                $transactions = $decoded['data']['list'] ?? [];
            } else {
                $error = "Gagal memuat riwayat: " . ($decoded['message'] ?? 'Unknown');
                Log::error('Transaction history error: ' . json_encode($decoded));
            }
        } catch (\Exception $e) {
            $error = "Sistem Error: " . $e->getMessage();
            Log::error('Transaction exception: ' . $e->getMessage());
        }

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'error'        => $error,
        ]);
    }
}
