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

class ValidateController extends Controller
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

    // GET /validate
    public function index()
    {
        return Inertia::render('Validate/Index');
    }

    // POST /validate/check
    public function check(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate(['msisdn' => 'required|string|starts_with:628']);

        try {
            $path = "api/v8/auth/check-dukcapil";
            $payload = [
                "with_bizon"          => true,
                "with_family_plan"    => true,
                "is_enterprise"       => false,
                "with_optimus"        => true,
                "lang"                => "en",
                "msisdn"              => $request->msisdn,
                "with_regist_status"  => true,
                "with_enterprise"     => true
            ];
            $res = $this->sendRequest($path, $payload, $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
