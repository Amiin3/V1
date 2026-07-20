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

class StoreController extends Controller
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

    // GET /store/segments
    public function segments(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $idToken = $activeUser['tokens']['id_token'];
        $segments = [];
        $error = null;
        $isEnterprise = $request->query('enterprise', '0') === '1';

        try {
            $path = "api/v8/configs/store/segments";
            $payload = ["is_enterprise" => $isEnterprise, "lang" => "en"];
            $res = $this->sendRequest($path, $payload, $idToken);

            if (($res['status'] ?? '') === 'SUCCESS') {
                $segments = $res['data']['store_segments'] ?? [];
            } else {
                $error = 'Gagal memuat segmen.';
            }
        } catch (\Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            Log::error('Store segments: ' . $e->getMessage());
        }

        return Inertia::render('Store/Segments', [
            'segments'     => $segments,
            'error'        => $error,
            'isEnterprise' => $isEnterprise,
        ]);
    }

    // GET /store/families
    public function families(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $idToken = $activeUser['tokens']['id_token'];
        $subscriptionType = $activeUser['subscription_type'] ?? 'PREPAID';
        $isEnterprise = $request->query('enterprise', '0') === '1';
        $familyList = [];
        $error = null;

        try {
            $path = "api/v8/xl-stores/options/search/family-list";
            $payload = [
                "is_enterprise" => $isEnterprise,
                "subs_type"     => $subscriptionType,
                "lang"          => "en"
            ];
            $res = $this->sendRequest($path, $payload, $idToken);

            if (($res['status'] ?? '') === 'SUCCESS') {
                $familyList = $res['data']['family_list'] ?? [];
            } else {
                $error = 'Gagal memuat family list.';
            }
        } catch (\Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            Log::error('Store families: ' . $e->getMessage());
        }

        return Inertia::render('Store/Families', [
            'familyList'       => $familyList,
            'error'            => $error,
            'isEnterprise'     => $isEnterprise,
            'subscriptionType' => $subscriptionType,
        ]);
    }

    // GET /store/packages
    public function packages(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $idToken = $activeUser['tokens']['id_token'];
        $subscriptionType = $activeUser['subscription_type'] ?? 'PREPAID';
        $isEnterprise = $request->query('enterprise', '0') === '1';
        $packageList = [];
        $error = null;

        try {
            $path = "api/v9/xl-stores/options/search";
            $payload = [
                "is_enterprise" => $isEnterprise,
                "filters" => [
                    ["unit" => "THOUSAND", "id" => "FIL_SEL_P", "type" => "PRICE", "items" => []],
                    ["unit" => "GB", "id" => "FIL_SEL_MQ", "type" => "DATA_TYPE", "items" => []],
                    ["unit" => "PACKAGE_NAME", "id" => "FIL_PKG_N", "type" => "PACKAGE_NAME", "items" => [["id" => "", "label" => ""]]],
                    ["unit" => "DAY", "id" => "FIL_SEL_V", "type" => "VALIDITY", "items" => []]
                ],
                "substype"    => $subscriptionType,
                "text_search" => "",
                "lang"        => "en"
            ];
            $res = $this->sendRequest($path, $payload, $idToken);

            if (($res['status'] ?? '') === 'SUCCESS') {
                $packageList = $res['data']['package_list'] ?? [];
            } else {
                $error = 'Gagal memuat packages.';
            }
        } catch (\Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            Log::error('Store packages: ' . $e->getMessage());
        }

        return Inertia::render('Store/Packages', [
            'packages'         => $packageList,
            'error'            => $error,
            'isEnterprise'     => $isEnterprise,
            'subscriptionType' => $subscriptionType,
        ]);
    }

    // GET /store/redeemables
    public function redeemables(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $idToken = $activeUser['tokens']['id_token'];
        $isEnterprise = $request->query('enterprise', '0') === '1';
        $categories = [];
        $error = null;

        try {
            $path = "api/v8/personalization/redeemables";
            $payload = ["is_enterprise" => $isEnterprise, "lang" => "en"];
            $res = $this->sendRequest($path, $payload, $idToken);

            if (($res['status'] ?? '') === 'SUCCESS') {
                $categories = $res['data']['categories'] ?? [];
            } else {
                $error = 'Gagal memuat redeemables.';
            }
        } catch (\Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            Log::error('Redeemables: ' . $e->getMessage());
        }

        return Inertia::render('Store/Redeemables', [
            'categories'   => $categories,
            'error'        => $error,
            'isEnterprise' => $isEnterprise,
        ]);
    }
}
