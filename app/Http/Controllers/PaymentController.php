<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProviderAuth;
use App\Services\CryptoService;
use App\Services\DecoyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentController extends Controller
{
    private $authService, $crypto, $decoyService;

    public function __construct(ProviderAuth $authService, CryptoService $crypto, DecoyService $decoyService)
    {
        $this->authService = $authService;
        $this->crypto = $crypto;
        $this->decoyService = $decoyService;
    }

    private function resolveIndex($array, $idx)
    {
        $idx = (int) $idx;
        if ($idx < 0) $idx = count($array) + $idx;
        return isset($array[$idx]) ? $idx : 0;
    }

    private function sendRequest($path, $payload, $idToken)
    {
        $apiKey = $this->crypto->getApiKey();
        $encrypted = $this->crypto->encryptSignXData('POST', $path, $idToken, $payload);
        $baseUrl = config('provider.base_api_url') ?: env('PROVIDER_BASE_URL', 'https://api.myxl.xlaxiata.co.id');
        
        $xtime = $encrypted['encrypted_body']['xtime'] ?? (time() * 1000);
        $sigTimeSec = (int) ($xtime / 1000);
        $xRequestAt = Carbon::createFromTimestamp($sigTimeSec, 'Asia/Jakarta')->format('Y-m-d\TH:i:s.vP');

        $headers = [
            'host' => parse_url($baseUrl, PHP_URL_HOST), 'content-type' => 'application/json; charset=utf-8',
            'user-agent' => config('provider.user_agent') ?: env('PROVIDER_UA', ''),
            'x-api-key' => $apiKey, 'authorization' => 'Bearer ' . $idToken,
            'x-hv' => 'v3', 'x-signature-time' => (string) $sigTimeSec,
            'x-signature' => $encrypted['x_signature'], 'x-request-id' => (string) Str::uuid(),
            'x-request-at' => $xRequestAt, 'x-version-app' => '8.9.0',
        ];
        
        $response = Http::withHeaders($headers)->timeout(30)->post($baseUrl . '/' . $path, $encrypted['encrypted_body']);
        $decrypted = $this->crypto->decryptXData($response->json());
        return is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
    }

    public function buy(Request $request)
    {
        $activeUser = $this->authService->getActiveUser();
        if (!$activeUser) return response()->json(['status' => 'FAILED', 'error' => 'Unauthorized'], 401);

        try {
            $tokens = $activeUser['tokens']; $idToken = $tokens['id_token'];
            $subscriptionType = $activeUser['subscription_type'] ?? 'PREPAID';
            $prefix = in_array($subscriptionType, ['PRIORITAS','PRIOHYBRID','GO']) ? 'prio-' : 'default-';
            
            $price = $request->price; 
            $tokenConfirmation = $request->token_confirmation;
            $paymentFor = $request->payment_for ?? 'BUY_PACKAGE'; 
            $itemName = $request->item_name ?? 'Paket';
            $optionCode = $request->option_code;
            
            // FIX 1: Selalu pastikan mengambil harga LIVE jika kosong atau mencurigakan
            if (empty($tokenConfirmation) || empty($itemName) || empty($price) || $price < 100) {
                $detailRes = $this->sendRequest('api/v8/xl-stores/options/detail', [
                    "is_transaction_routine" => false, "migration_type" => "NONE", "package_family_code" => "", 
                    "family_role_hub" => "", "is_autobuy" => false, "is_enterprise" => false, "is_shareable" => false, 
                    "is_migration" => false, "lang" => "en", "package_option_code" => $optionCode,
                    "is_upsell_pdp" => false, "package_variant_code" => ""
                ], $idToken);
                
                if (isset($detailRes['data'])) {
                    $pkg = $detailRes['data'];
                    $tokenConfirmation = $pkg['token_confirmation'] ?? $tokenConfirmation;
                    $itemName = $pkg['package_option']['name'] ?? $itemName;
                    $paymentFor = $pkg['package_family']['payment_for'] ?: $paymentFor;
                    $price = $pkg['package_option']['price']; // Ambil harga LIVE dari API
                }
            }

            $price = is_numeric($price) ? (int)$price : 0;
            $items = [[
                "item_code" => $optionCode, "product_type" => "", "item_price" => $price,
                "item_name" => $itemName, "tax" => 0, "token_confirmation" => $tokenConfirmation
            ]];
            
            if ($request->decoy_type) {
                $decoy = $this->decoyService->getDecoy($request->decoy_type, $prefix);
                if ($decoy) {
                    $decoyDetail = $this->getDecoyOptionCode($decoy, $idToken);
                    if ($decoyDetail) {
                        // FIX 2: Jangan gunakan harga config lokal, wajib ambil harga LIVE dari API
                        $liveDecoyPrice = isset($decoyDetail['price']) ? (int) $decoyDetail['price'] : (int) $decoy['price'];
                        $items[] = [
                            "item_code" => $decoyDetail['option_code'], "product_type" => "",
                            "item_price" => $liveDecoyPrice, "item_name" => "Decoy " . $request->decoy_type,
                            "tax" => 0, "token_confirmation" => $decoyDetail['token_confirmation'] ?? '',
                        ];
                    }
                }
            }

            $tokenConfirmationIdx = (int) $request->input('token_confirmation_idx', count($items) > 1 ? 1 : 0);
            
            // FIX 3: Paksa totalAmount menjumlahkan SELURUH harga item di keranjang. 
            // Abaikan request->overwrite_amount dari panel karena bisa bikin INVALID_PRICE jika salah total.
            $totalAmount = 0;
            foreach ($items as $itm) {
                $totalAmount += (int) $itm['item_price'];
            }

            $settlementResult = $this->doSettlement($items, $request->payment_method, $paymentFor, $totalAmount, $tokens, $request->wallet_number, $tokenConfirmationIdx);
            
            // Jika ternyata ada koreksi harga (Bizz-err) dari server, retry.
            if (isset($settlementResult['message']) && strpos($settlementResult['message'], 'Bizz-err.Amount.Total') !== false) {
                preg_match('/=\s*(\d+)/', $settlementResult['message'], $matches);
                if (isset($matches[1])) {
                    $validAmount = (int) $matches[1];
                    Log::warning("[CY STORE WAR ENGINE] Auto-Retry triggered! Adjusting total amount from {$totalAmount} to {$validAmount}");
                    $settlementResult = $this->doSettlement($items, $request->payment_method, $paymentFor, $validAmount, $tokens, $request->wallet_number, $tokenConfirmationIdx);
                }
            }
            
            Log::info('[CY STORE WAR ENGINE] Final Settlement Response: ', (array) $settlementResult);
            return $this->processResponse($settlementResult, $request);

        } catch (\Exception $e) {
            Log::error('[CY STORE WAR ENGINE] Payment buy error: ' . $e->getMessage());
            return $request->wantsJson() ? response()->json(['status' => 'FAILED', 'error' => 'Error: ' . $e->getMessage()], 500) : back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function doSettlement($items, $paymentMethod, $paymentFor, $totalAmount, $tokens, $walletNumber = '', $tokenConfirmationIdx = 0)
    {
        $idToken = $tokens['id_token']; $accessToken = $tokens['access_token'];
        $idx = $this->resolveIndex($items, $tokenConfirmationIdx);
        
        $paymentMethod = strtoupper($paymentMethod ?? 'BALANCE');
        if (in_array($paymentMethod, ['PULSA', 'PULSA N KALI', ''])) $paymentMethod = 'BALANCE';

        $paymentMethodsRes = $this->sendRequest('payments/api/v8/payment-methods-option', [
            "payment_type" => "PURCHASE", "is_enterprise" => false,
            "payment_target" => $items[$idx]["item_code"], "lang" => "en",
            "is_referral" => false, "token_confirmation" => $items[$idx]["token_confirmation"]
        ], $idToken);

        if (($paymentMethodsRes['status'] ?? '') !== 'SUCCESS') return ['error' => 'Gagal mendapatkan token_payment.', 'detail' => $paymentMethodsRes];
        
        $tokenPayment = $paymentMethodsRes['data']['token_payment']; 
        $tsToSign = $paymentMethodsRes['data']['timestamp'];

        if (in_array($paymentMethod, ['DANA', 'SHOPEEPAY', 'GOPAY', 'OVO'])) {
            $settlementPath = 'payments/api/v8/settlement-multipayment/ewallet';
        } elseif ($paymentMethod === 'QRIS') {
            $settlementPath = 'payments/api/v8/settlement-multipayment/qris';
        } else {
            $paymentMethod = 'BALANCE'; 
            $settlementPath = 'payments/api/v8/settlement-multipayment';
            $encField = method_exists($this->crypto, 'buildEncryptedField') ? $this->crypto->buildEncryptedField(true) : "";
            
            $lastItem = end($items);
            $originalPrice = (int) $lastItem['item_price'];

            $settlementPayload = [
                "total_discount" => 0, 
                "is_enterprise" => false, 
                "payment_token" => "", 
                "token_payment" => $tokenPayment,
                "activated_autobuy_code" => "", 
                "cc_payment_type" => "", 
                "is_myxl_wallet" => false, 
                "pin" => "",
                "ewallet_promo_id" => "", 
                "members" => [], 
                "total_fee" => 0, 
                "fingerprint" => "", 
                "autobuy_threshold_setting" => [
                    "label" => "",
                    "type" => "",
                    "value" => 0
                ],
                "is_use_point" => false, 
                "lang" => "en", 
                "payment_method" => "BALANCE", 
                "timestamp" => time(), 
                "points_gained" => 0, 
                "can_trigger_rating" => false, 
                "akrab_members" => [], 
                "akrab_parent_alias" => "", 
                "referral_unique_code" => "",
                "coupon" => "", 
                "payment_for" => $paymentFor, 
                "with_upsell" => false, 
                "topup_number" => "", 
                "stage_token" => "", 
                "authentication_id" => "", 
                "encrypted_payment_token" => $encField, 
                "token" => "", 
                "token_confirmation" => "", 
                "access_token" => $accessToken, 
                "wallet_number" => "", 
                "encrypted_authentication_id" => $encField,
                "additional_data" => [
                    "original_price" => $originalPrice, 
                    "is_spend_limit_temporary" => false, 
                    "migration_type" => "",
                    "akrab_m2m_group_id" => "false", 
                    "spend_limit_amount" => 0, 
                    "is_spend_limit" => false, 
                    "mission_id" => "", 
                    "tax" => 0, 
                    "quota_bonus" => 0, 
                    "cashtag" => "", 
                    "is_family_plan" => false, 
                    "combo_details" => [], 
                    "is_switch_plan" => false, 
                    "discount_recurring" => 0, 
                    "is_akrab_m2m" => false, 
                    "balance_type" => "PREPAID_BALANCE", 
                    "has_bonus" => false, 
                    "discount_promo" => 0
                ], 
                "total_amount" => $totalAmount, 
                "is_using_autobuy" => false, 
                "items" => $items,
            ];
        }

        $paymentTargets = collect($items)->pluck('item_code')->implode(';');
        $xSignature = $this->crypto->callCryptoApi('/sign-payment', [
            'access_token' => $accessToken, 'sig_time_sec' => (int) $tsToSign, 'package_code' => $paymentTargets, 
            'token_payment' => $tokenPayment, 'payment_method' => $paymentMethod, 'payment_for' => $paymentFor, 'path' => $settlementPath,
        ])['x_signature'] ?? null;

        $encrypted = $this->crypto->encryptSignXData('POST', $settlementPath, $idToken, $settlementPayload);
        $baseUrl = config('provider.base_api_url') ?: env('PROVIDER_BASE_URL', 'https://api.myxl.xlaxiata.co.id');
        $xtime = $encrypted['encrypted_body']['xtime'] ?? (time() * 1000); $sigTimeSec = (int) ($xtime / 1000);
        $xRequestAt = Carbon::createFromTimestamp($sigTimeSec, 'Asia/Jakarta')->format('Y-m-d\TH:i:s.vP');
        
        $headers = [
            'host' => parse_url($baseUrl, PHP_URL_HOST), 'content-type' => 'application/json; charset=utf-8',
            'user-agent' => config('provider.user_agent') ?: env('PROVIDER_UA', ''), 'x-api-key' => $this->crypto->getApiKey(), 
            'authorization' => 'Bearer ' . $idToken, 'x-hv' => 'v3', 'x-signature-time' => (string) $sigTimeSec,
            'x-signature' => $xSignature, 'x-request-id' => (string) Str::uuid(), 'x-request-at' => $xRequestAt, 'x-version-app' => '8.9.0',
        ];

        $response = Http::withHeaders($headers)->timeout(30)->post($baseUrl . '/' . $settlementPath, $encrypted['encrypted_body']);
        $decrypted = $this->crypto->decryptXData($response->json());
        $resultArr = is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
        
        if (isset($resultArr['status']) && $resultArr['status'] !== 'SUCCESS') {
            Log::error("[CY STORE WAR ENGINE] Settlement Failed. Payload sent: ", isset($settlementPayload) ? $settlementPayload : []);
        }
        
        return $resultArr;
    }

    private function processResponse($result, Request $request)
    {
        if (isset($result['error'])) return $request->wantsJson() ? response()->json(['status' => 'FAILED', 'error' => $result['error']], 400) : back()->withErrors(['error' => $result['error']]);
        if (($result['status'] ?? '') !== 'SUCCESS') {
            $msg = $result['message'] ?? 'API Error - Gagal memproses data.';
            return $request->wantsJson() ? response()->json(['status' => 'FAILED', 'message' => $msg, 'detail' => $result]) : back()->withErrors(['error' => $msg]);
        }
        $dataReturn = $result['data'] ?? null;
        if ($request->wantsJson()) return response()->json(['status' => 'SUCCESS', 'data' => $dataReturn]);
        if (isset($dataReturn['deeplink'])) return back()->with('success', 'Transaksi berhasil dibuat!')->with('deeplink', $dataReturn['deeplink']);
        return back()->with('success', 'Pembelian sukses diselesaikan!');
    }

    private function getDecoyOptionCode($decoy, $idToken)
    {
        try {
            $familyData = $this->sendRequest('api/v8/xl-stores/options/list', [
                "is_show_tagging_tab" => true, "is_dedicated_event" => true, "is_transaction_routine" => false, 
                "migration_type" => $decoy['migration_type'] ?? "NONE", "package_family_code" => $decoy['family_code'], 
                "is_autobuy" => false, "is_enterprise" => $decoy['is_enterprise'] ?? false, "is_pdlp" => true,
                "referral_code" => "", "is_migration" => false, "lang" => "en"
            ], $idToken);
            if (!isset($familyData['data']['package_variants'])) return null;
            foreach ($familyData['data']['package_variants'] as $variant) {
                if ($variant['package_variant_code'] === $decoy['variant_code']) {
                    foreach ($variant['package_options'] as $option) {
                        if ($option['order'] == $decoy['order']) {
                            $detailRes = $this->sendRequest('api/v8/xl-stores/options/detail', [
                                "is_transaction_routine" => false, "migration_type" => "NONE", "package_family_code" => $decoy['family_code'], 
                                "family_role_hub" => "", "is_autobuy" => false, "is_enterprise" => $decoy['is_enterprise'] ?? false,
                                "is_shareable" => false, "is_migration" => false, "lang" => "en", "package_option_code" => $option['package_option_code'],
                                "is_upsell_pdp" => false, "package_variant_code" => $variant['package_variant_code']                                
                            ], $idToken);
                            if (isset($detailRes['data'])) {
                                return [
                                    'option_code' => $option['package_option_code'], 
                                    'token_confirmation' => $detailRes['data']['token_confirmation'] ?? '',
                                    'price' => $detailRes['data']['package_option']['price'] ?? null
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {}
        return null;
    }
}
