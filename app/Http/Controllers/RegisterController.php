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

class RegisterController extends Controller
{
    private $crypto;

    public function __construct(CryptoService $crypto)
    {
        $this->crypto = $crypto;
    }

    // GET /register
    public function index()
    {
        return Inertia::render('Register/Index');
    }

    // POST /register/dukcapil
    public function dukcapil(Request $request)
    {
        $request->validate([
            'msisdn' => 'required|string|starts_with:628',
            'nik'    => 'required|string|size:16',
            'kk'     => 'required|string|size:16',
        ]);

        $apiKey = $this->crypto->getApiKey();
        $path = "api/v8/auth/regist/dukcapil";
        $payload = [
            "msisdn" => $request->msisdn,
            "kk"     => $request->kk,
            "nik"    => $request->nik,
            "lang"   => "en"
        ];

        // Request ini TANPA token (id_token kosong)
        $encrypted = $this->crypto->encryptSignXData('POST', $path, '', $payload);

        $now = Carbon::now('Asia/Jakarta');
        $baseUrl = config('provider.base_url') ?: env('PROVIDER_BASE_URL', 'https://api.myxl.xlaxiata.co.id');
        $centiseconds = str_pad(floor($now->micro / 10000), 2, '0', STR_PAD_LEFT);
        $xRequestAt = $now->format('Y-m-d\TH:i:s.') . $centiseconds . $now->format('P');
        $xSignatureTime = (string) (int) ($encrypted['encrypted_body']['xtime'] / 1000);

        try {
            $response = Http::withHeaders([
                'host'              => parse_url($baseUrl, PHP_URL_HOST),
                'content-type'      => 'application/json; charset=utf-8',
                'user-agent'        => config('provider.user_agent') ?? env('PROVIDER_UA', ''),
                'x-api-key'         => $apiKey,
                'x-hv'              => 'v3',
                'x-signature-time'  => $xSignatureTime,
                'x-signature'       => $encrypted['x_signature'],
                'x-request-id'      => (string) Str::uuid(),
                'x-request-at'      => $xRequestAt,
                'x-version-app'     => '8.9.0',
            ])->timeout(30)->post($baseUrl . '/' . $path, $encrypted['encrypted_body']);

            $decrypted = $this->crypto->decryptXData($response->json());
            $result = is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Dukcapil: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
