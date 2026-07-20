<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\ProviderAuth;
use App\Services\CryptoService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class HotController extends Controller
{
    protected $authService;
    protected $crypto;

    public function __construct(ProviderAuth $authService, CryptoService $crypto)
    {
        $this->authService = $authService;
        $this->crypto = $crypto;
    }

    // ========== JEMBATAN REQUEST API PROVIDER ==========
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

    // 1. ROUTE: /packages/hot (Menampilkan HOT 1)
    public function index()
    {
        if (!$this->authService->getActiveUser()) return redirect()->route('login');
        $hotList = [];
        try {
            $res = Http::timeout(30)->get('https://me.mashu.lol/pg-hot.json');
            if ($res->successful()) $hotList = $res->json();
        } catch (\Exception $e) {}
        return Inertia::render('Hot/Index', ['hotList' => $hotList]);
    }

    // 2. ROUTE: /packages/hot2 (Menampilkan HOT 2)
    public function hot2Index()
    {
        if (!$this->authService->getActiveUser()) return redirect()->route('login');
        $hotList = [];
        try {
            $res = Http::timeout(30)->get('https://me.mashu.lol/pg-hot2.json');
            if ($res->successful()) $hotList = $res->json();
        } catch (\Exception $e) {}
        return Inertia::render('Hot/Hot2', ['hotList' => $hotList]);
    }

    // 3. ROUTE: /packages/detail/... (Menangkap klik "Beli Paket" dari Family Code)
    public function detail($familyCode, $variantName, $order)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');
        
        $idToken = $activeUser['tokens']['id_token'];
        $familyData = null;

        // Cari Family Data
        foreach (["NONE", "PRE_TO_PRIOH", "PRIOH_TO_PRIO", "PRIO_TO_PRIOH"] as $mt) {
            if ($familyData) break;
            foreach ([false, true] as $ie) {
                try {
                    $res = $this->sendRequest("api/v8/xl-stores/options/list", [
                        "is_show_tagging_tab" => true,
                        "is_dedicated_event" => true,
                        "is_transaction_routine" => false,
                        "migration_type" => $mt,
                        "package_family_code" => $familyCode,
                        "is_autobuy" => false,
                        "is_enterprise" => $ie,
                        "is_pdlp" => true,
                        "referral_code" => "",
                        "is_migration" => false,
                        "lang" => "en"
                    ], $idToken);
                    
                    if (isset($res['status']) && $res['status'] === 'SUCCESS' && !empty($res['data']['package_family']['name'])) {
                        $familyData = $res['data'];
                        break;
                    }
                } catch (\Exception $e) {}
            }
        }

        if (!$familyData) {
            return Inertia::render('Hot/Detail', ['error' => 'Gagal menemukan paket dari Provider.']);
        }

        // Cari Option Code berdasarkan Variant & Order
        $optionCode = null;
        foreach ($familyData['package_variants'] ?? [] as $var) {
            if ($var['package_variant_code'] == $variantName || $var['name'] == $variantName) {
                foreach ($var['package_options'] ?? [] as $opt) {
                    if ($opt['order'] == $order) {
                        $optionCode = $opt['package_option_code'];
                        break 2;
                    }
                }
            }
        }

        if (!$optionCode) {
            return Inertia::render('Hot/Detail', ['error' => 'Opsi paket tidak tersedia.']);
        }

        // Lanjut tarik detail aslinya menggunakan Option Code
        return $this->detailByOptionCode($optionCode);
    }

    // 4. ROUTE: Menarik informasi Benefit & Harga Final dari XL
    public function detailByOptionCode($optionCode)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');
        
        try {
            $res = $this->sendRequest("api/v8/xl-stores/options/detail", [
                "is_transaction_routine" => false,
                "migration_type" => "NONE",
                "package_family_code" => "",
                "family_role_hub" => "",
                "is_autobuy" => false,
                "is_enterprise" => false,
                "is_shareable" => false,
                "is_migration" => false,
                "lang" => "en",
                "package_option_code" => $optionCode,
                "is_upsell_pdp" => false,
                "package_variant_code" => ""
            ], $activeUser['tokens']['id_token']);

            if (isset($res['status']) && $res['status'] === 'SUCCESS') {
                return Inertia::render('Hot/Detail', ['package' => $res['data']]);
            }
            return Inertia::render('Hot/Detail', ['error' => $res['message'] ?? 'Gagal memuat detail dari XL.']);
        } catch (\Exception $e) {
            return Inertia::render('Hot/Detail', ['error' => $e->getMessage()]);
        }
    }

    // 5. ROUTE: /packages/family-list (API POST untuk Pencarian Family Code)
    public function familyPackages(Request $request)
    {
        $request->validate(['family_code' => 'required|string']);
        $familyCode = $request->family_code;

        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Sesi Berakhir, silakan login ulang.'], 401);
        
        $idToken = $activeUser['tokens']['id_token'];
        $familyData = null;

        foreach (["NONE", "PRE_TO_PRIOH", "PRIOH_TO_PRIO", "PRIO_TO_PRIOH"] as $mt) {
            if ($familyData) break;
            foreach ([false, true] as $ie) {
                try {
                    $res = $this->sendRequest("api/v8/xl-stores/options/list", [
                        "is_show_tagging_tab" => true,
                        "is_dedicated_event" => true,
                        "is_transaction_routine" => false,
                        "migration_type" => $mt,
                        "package_family_code" => $familyCode,
                        "is_autobuy" => false,
                        "is_enterprise" => $ie,
                        "is_pdlp" => true,
                        "referral_code" => "",
                        "is_migration" => false,
                        "lang" => "en"
                    ], $idToken);

                    if (isset($res['status']) && $res['status'] === 'SUCCESS' && !empty($res['data']['package_family']['name'])) {
                        $familyData = $res['data'];
                        break;
                    }
                } catch (\Exception $e) {}
            }
        }

        if (!$familyData) return response()->json(['error' => 'Gagal mencari paket dari Provider.']);

        $packages = [];
        foreach ($familyData['package_variants'] ?? [] as $variant) {
            $variantCode = $variant['package_variant_code'] ?? '';
            $variantName = $variant['name'] ?? '';
            foreach ($variant['package_options'] ?? [] as $option) {
                $packages[] = [
                    'family_code'  => $familyCode,
                    'variant_code' => $variantCode,
                    'variant_name' => $variantName,
                    'option_name'  => $option['name'] ?? '',
                    'order'        => $option['order'] ?? 0,
                    'price'        => $option['price'] ?? 0,
                    'currency'     => $option['currency'] ?? 'Rp',
                ];
            }
        }
        return response()->json(['packages' => $packages]);
    }
}
