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

class FamilyController extends Controller
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

    public function index()
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $idToken = $activeUser['tokens']['id_token'];
        $familyData = null;
        $error = null;

        try {
            $path = "sharings/api/v8/family-plan/member-info";
            $payload = ["group_id" => 0, "is_enterprise" => false, "lang" => "en"];
            $res = $this->sendRequest($path, $payload, $idToken);

            if (isset($res['status']) && $res['status'] === 'SUCCESS') {
                $familyData = $res['data']['member_info'] ?? null;
                if (empty($familyData['plan_type'])) {
                    $error = "Anda bukan organizer Family Plan.";
                }
            } else {
                $error = "Gagal memuat data: " . ($res['message'] ?? 'Unknown');
            }
        } catch (\Exception $e) {
            $error = "Error: " . $e->getMessage();
            Log::error('Family index error: ' . $e->getMessage());
        }

        return Inertia::render('Family/Index', [
            'familyData' => $familyData,
            'error'      => $error,
        ]);
    }

    public function validateMsisdn(Request $request)
    {
        $request->validate(['msisdn' => 'required|string']);
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        try {
            $path = "api/v8/auth/check-dukcapil";
            $payload = [
                "with_bizon" => true,
                "with_family_plan" => true,
                "is_enterprise" => false,
                "with_optimus" => true,
                "lang" => "en",
                "msisdn" => $request->msisdn,
                "with_regist_status" => true,
                "with_enterprise" => true
            ];
            $res = $this->sendRequest($path, $payload, $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function changeMember(Request $request)
    {
        $request->validate([
            'slot_id' => 'required|integer',
            'family_member_id' => 'required|string',
            'msisdn' => 'required|string',
            'parent_alias' => 'required|string',
            'child_alias' => 'required|string',
        ]);
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        try {
            $path = "sharings/api/v8/family-plan/change-member";
            $payload = [
                "parent_alias" => $request->parent_alias,
                "is_enterprise" => false,
                "slot_id" => $request->slot_id,
                "alias" => $request->child_alias,
                "lang" => "en",
                "msisdn" => $request->msisdn,
                "family_member_id" => $request->family_member_id
            ];
            $res = $this->sendRequest($path, $payload, $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function removeMember(Request $request)
    {
        $request->validate(['family_member_id' => 'required|string']);
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        try {
            $path = "sharings/api/v8/family-plan/remove-member";
            $payload = [
                "is_enterprise" => false,
                "family_member_id" => $request->family_member_id,
                "lang" => "en"
            ];
            $res = $this->sendRequest($path, $payload, $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function setQuotaLimit(Request $request)
    {
        $request->validate([
            'family_member_id' => 'required|string',
            'new_allocation_mb' => 'required|integer|min:0',
            'original_allocation' => 'required|integer'
        ]);
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        try {
            $originalAllocation = (int) $request->original_allocation;
            $newAllocation = (int) $request->new_allocation_mb * 1024 * 1024;

            $path = "sharings/api/v8/family-plan/allocate-quota";
            $payload = [
                "is_enterprise" => false,
                "member_allocations" => [[
                    "new_text_allocation" => 0,
                    "original_text_allocation" => 0,
                    "original_voice_allocation" => 0,
                    "original_allocation" => $originalAllocation,
                    "new_voice_allocation" => 0,
                    "message" => "",
                    "new_allocation" => $newAllocation,
                    "family_member_id" => $request->family_member_id,
                    "status" => ""
                ]],
                "lang" => "en"
            ];
            $res = $this->sendRequest($path, $payload, $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
