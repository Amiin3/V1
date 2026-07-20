<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bookmark;
use App\Services\ProviderAuth;
use App\Services\CryptoService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookmarkController extends Controller
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

    // GET /bookmarks
    public function index()
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $bookmarks = Bookmark::orderBy('id')->get();

        return Inertia::render('Bookmarks/Index', [
            'bookmarks' => $bookmarks,
        ]);
    }

    // POST /bookmarks/add
    public function add(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'family_code' => 'required|string',
            'family_name' => 'required|string',
            'is_enterprise' => 'boolean',
            'variant_name' => 'required|string',
            'option_name' => 'required|string',
            'order' => 'required|integer',
        ]);

        // Cek duplikat
        $exists = Bookmark::where('family_code', $request->family_code)
            ->where('variant_name', $request->variant_name)
            ->where('order', $request->order)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Bookmark sudah ada.'], 200);
        }

        Bookmark::create($request->all());

        return response()->json(['message' => 'Bookmark ditambahkan!']);
    }

    // POST /bookmarks/remove
    public function remove(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'family_code' => 'required|string',
            'is_enterprise' => 'boolean',
            'variant_name' => 'required|string',
            'order' => 'required|integer',
        ]);

        $deleted = Bookmark::where('family_code', $request->family_code)
            ->where('is_enterprise', $request->is_enterprise)
            ->where('variant_name', $request->variant_name)
            ->where('order', $request->order)
            ->delete();

        return response()->json(['message' => $deleted ? 'Bookmark dihapus.' : 'Bookmark tidak ditemukan.']);
    }

    // GET /bookmarks/detail/{family_code}/{variant_name}/{order}
    public function detail($familyCode, $variantName, $order)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $idToken = $activeUser['tokens']['id_token'];
        $bookmark = Bookmark::where('family_code', $familyCode)
            ->where('variant_name', $variantName)
            ->where('order', $order)
            ->first();

        if (!$bookmark) {
            return back()->with('error', 'Bookmark tidak ditemukan.');
        }

        try {
            // Dapatkan family data untuk mencari option_code
            $familyData = $this->sendRequest('api/v8/xl-stores/options/list', [
                "is_show_tagging_tab" => true,
                "is_dedicated_event" => true,
                "is_transaction_routine" => false,
                "migration_type" => "NONE",
                "package_family_code" => $familyCode,
                "is_autobuy" => false,
                "is_enterprise" => $bookmark->is_enterprise,
                "is_pdlp" => true,
                "referral_code" => "",
                "is_migration" => false,
                "lang" => "en"
            ], $idToken);

            $optionCode = null;
            if (isset($familyData['data']['package_variants'])) {
                foreach ($familyData['data']['package_variants'] as $variant) {
                    if ($variant['name'] === $variantName) {
                        foreach ($variant['package_options'] as $option) {
                            if ($option['order'] == $order) {
                                $optionCode = $option['package_option_code'];
                                break 2;
                            }
                        }
                    }
                }
            }

            if ($optionCode) {
                // Redirect ke halaman package detail (bisa diintegrasikan nanti)
                return redirect()->route('packages.detail', ['code' => $optionCode]);
            } else {
                return back()->with('error', 'Paket tidak ditemukan.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
