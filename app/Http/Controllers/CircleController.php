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

class CircleController extends Controller
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

    // ============= PAGE: Circle Info =============
    public function index()
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return redirect()->route('login');

        $idToken = $activeUser['tokens']['id_token'];
        $myNumber = $activeUser['number'] ?? '';
        $groupData = null;
        $members = [];
        $package = null;
        $spending = null;
        $error = null;
        $isOwner = false;

        try {
            // Get group data
            $groupRes = $this->sendRequest('family-hub/api/v8/groups/status', [
                "is_enterprise" => false,
                "lang" => "en"
            ], $idToken);

            if ($groupRes['status'] !== 'SUCCESS' || empty($groupRes['data']['group_id'])) {
                return Inertia::render('Circle/Index', [
                    'error' => 'Anda belum memiliki Circle.',
                    'groupData' => null,
                    'members' => [],
                    'package' => null,
                    'spending' => null,
                    'myNumber' => $myNumber,
                ]);
            }

            $groupData = $groupRes['data'];
            $groupId = $groupData['group_id'];

            // Get members
            $membersRes = $this->sendRequest('family-hub/api/v8/members/info', [
                "group_id" => $groupId,
                "is_enterprise" => false,
                "lang" => "en"
            ], $idToken);

            if ($membersRes['status'] === 'SUCCESS') {
                $membersData = $membersRes['data'];
                $members = $membersData['members'] ?? [];
                $package = $membersData['package'] ?? null;

                // Decrypt MSISDN untuk setiap member
                foreach ($members as &$member) {
                    if (!empty($member['msisdn'])) {
                        try {
                            $member['msisdn_raw'] = $this->crypto->decryptCircleMsisdn($member['msisdn']);
                        } catch (\Exception $e) {
                            $member['msisdn_raw'] = '<gagal dekripsi>';
                        }
                    } else {
                        $member['msisdn_raw'] = '';
                    }
                }

                // Cari parent
                $parentMemberId = '';
                $parentSubsId = '';
                foreach ($members as $m) {
                    if ($m['member_role'] === 'PARENT') {
                        $parentMemberId = $m['member_id'] ?? '';
                        $parentSubsId = $m['subscriber_number'] ?? '';
                        $isOwner = ($m['msisdn_raw'] == $myNumber);
                        break;
                    }
                }

                // Spending tracker
                if ($parentSubsId) {
                    $spendingRes = $this->sendRequest('gamification/api/v8/family-hub/spending-tracker', [
                        "is_enterprise" => false,
                        "parent_subs_id" => $parentSubsId,
                        "family_id" => $groupId,
                        "lang" => "en"
                    ], $idToken);
                    if ($spendingRes['status'] === 'SUCCESS') {
                        $spending = $spendingRes['data'];
                    }
                }
            }
        } catch (\Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            Log::error('Circle index: ' . $e->getMessage());
        }

        return Inertia::render('Circle/Index', [
            'groupData' => $groupData,
            'members'   => $members,
            'package'   => $package,
            'spending'  => $spending,
            'error'     => $error,
            'myNumber'  => $myNumber,
            'isOwner'   => $isOwner,
        ]);
    }

    // ============= ACTION: Create Circle =============
    public function create(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'parent_name' => 'required|string',
            'group_name' => 'required|string',
            'member_msisdn' => 'required|string',
            'member_name' => 'required|string',
        ]);

        try {
            $encryptedMsisdn = $this->crypto->encryptCircleMsisdn($request->member_msisdn);
            $res = $this->sendRequest('family-hub/api/v8/groups/create', [
                "access_token" => $activeUser['tokens']['access_token'],
                "parent_name" => $request->parent_name,
                "group_name" => $request->group_name,
                "is_enterprise" => false,
                "members" => [
                    ["msisdn" => $encryptedMsisdn, "name" => $request->member_name]
                ],
                "lang" => "en",
            ], $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============= ACTION: Validate MSISDN =============
    public function validateMember(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        try {
            $encrypted = $this->crypto->encryptCircleMsisdn($request->msisdn);
            $res = $this->sendRequest('family-hub/api/v8/members/validate', [
                "msisdn" => $encrypted,
                "is_enterprise" => false,
                "lang" => "en"
            ], $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============= ACTION: Invite Member =============
    public function invite(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'msisdn' => 'required|string',
            'name' => 'required|string',
            'group_id' => 'required|string',
            'member_id_parent' => 'required|string',
        ]);

        try {
            $encrypted = $this->crypto->encryptCircleMsisdn($request->msisdn);
            $res = $this->sendRequest('family-hub/api/v8/members/invite', [
                "access_token" => $activeUser['tokens']['access_token'],
                "group_id" => $request->group_id,
                "is_enterprise" => false,
                "members" => [
                    ["msisdn" => $encrypted, "name" => $request->name]
                ],
                "lang" => "en",
                "member_id_parent" => $request->member_id_parent
            ], $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============= ACTION: Remove Member =============
    public function removeMember(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'member_id' => 'required|string',
            'group_id' => 'required|string',
            'member_id_parent' => 'required|string',
            'is_last_member' => 'boolean',
        ]);

        try {
            $res = $this->sendRequest('family-hub/api/v8/members/remove', [
                "member_id" => $request->member_id,
                "group_id" => $request->group_id,
                "is_enterprise" => false,
                "is_last_member" => $request->is_last_member ?? false,
                "lang" => "en",
                "member_id_parent" => $request->member_id_parent
            ], $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============= ACTION: Accept Invitation =============
    public function acceptInvitation(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'group_id' => 'required|string',
            'member_id' => 'required|string',
        ]);

        try {
            $res = $this->sendRequest('family-hub/api/v8/groups/accept-invitation', [
                "access_token" => $activeUser['tokens']['access_token'],
                "group_id" => $request->group_id,
                "member_id" => $request->member_id,
                "is_enterprise" => false,
                "lang" => "en"
            ], $activeUser['tokens']['id_token']);
            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
