<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CiamService
{
    protected $baseUrl;
    protected $basicAuth;
    protected $userAgent;
    protected $crypto;

    public function __construct(CryptoService $crypto)
    {
        $this->baseUrl = config('provider.base_ciam_url');
        $this->basicAuth = config('provider.basic_auth');
        $this->userAgent = config('provider.user_agent');
        $this->crypto = $crypto;
    }

    public function requestOtp($msisdn)
    {
        $url = $this->baseUrl . '/realms/xl-ciam/auth/otp';
        $now = Carbon::now('Asia/Jakarta');
        $requestAt = $this->crypto->javaLikeTimestamp($now);
        $requestId = Str::uuid()->toString();
        $deviceId = $this->crypto->axDeviceId();
        $fpKey = $this->crypto->loadAxFp();

        $response = Http::withHeaders([
            'Accept-Encoding' => 'gzip, deflate, br',
            'Authorization' => 'Basic ' . $this->basicAuth,
            'Ax-Device-Id' => $deviceId,
            'Ax-Fingerprint' => $fpKey,
            'Ax-Request-At' => $requestAt,
            'Ax-Request-Device' => 'samsung',
            'Ax-Request-Device-Model' => 'SM-N935F',
            'Ax-Request-Id' => $requestId,
            'Ax-Substype' => 'PREPAID',
            'Host' => str_replace('https://', '', $this->baseUrl),
            'User-Agent' => $this->userAgent,
        ])->get($url, [
            'contact' => $msisdn,
            'contactType' => 'SMS',
            'alternateContact' => 'false'
        ]);

        $data = $response->json();
        Log::debug('OTP request response', $data ?? []);

        if (!isset($data['subscriber_id'])) {
            throw new \Exception("Gagal request OTP: " . json_encode($data));
        }
        return $data['subscriber_id'];
    }

    public function submitOtp($msisdn, $otpCode, $contactType = 'SMS')
    {
        $url = $this->baseUrl . '/realms/xl-ciam/protocol/openid-connect/token';
        $now = Carbon::now('Asia/Jakarta');
        $tsForSign = $this->crypto->tsGmt7WithoutColon($now);
        $tsHeader = $this->crypto->tsGmt7WithoutColon($now->copy()->subMinutes(5));

        $signature = $this->crypto->axApiSignature($tsForSign, $msisdn, $otpCode, $contactType);

        $deviceId = $this->crypto->axDeviceId();
        $fpKey = $this->crypto->loadAxFp();

        $response = Http::asForm()->withHeaders([
            'Accept-Encoding' => 'gzip, deflate, br',
            'Authorization' => 'Basic ' . $this->basicAuth,
            'Ax-Api-Signature' => $signature,
            'Ax-Device-Id' => $deviceId,
            'Ax-Fingerprint' => $fpKey,
            'Ax-Request-At' => $tsHeader,
            'Ax-Request-Device' => 'samsung',
            'Ax-Request-Device-Model' => 'SM-N935F',
            'Ax-Request-Id' => Str::uuid()->toString(),
            'Ax-Substype' => 'PREPAID',
            'User-Agent' => $this->userAgent,
        ])->post($url, [
            'contactType' => $contactType,
            'code' => $otpCode,
            'grant_type' => 'password',
            'contact' => $msisdn,
            'scope' => 'openid'
        ]);

        $data = $response->json();
        Log::debug('OTP submit response', $data ?? []);

        if (isset($data['error'])) {
            throw new \Exception("Gagal Login: " . json_encode($data));
        }

        if (!isset($data['id_token'])) {
            throw new \Exception("Respons tidak mengandung id_token.");
        }

        return $data;
    }
}
