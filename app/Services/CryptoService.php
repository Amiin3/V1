<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CryptoService
{
    protected $baseCryptoUrl;
    protected $aesKeyAscii;
    protected $axFpKey;

    public function __construct()
    {
        $this->baseCryptoUrl = config('provider.base_crypto_url');
        $this->aesKeyAscii = config('provider.aes_key');
        $this->axFpKey = config('provider.fp_key');
    }

    public function getApiKey()
    {
        if (Storage::disk('local')->exists('api.key')) {
            return trim(Storage::disk('local')->get('api.key'));
        }
        return config('provider.api_key');
    }

    public function saveApiKey($key) { Storage::disk('local')->put('api.key', trim($key)); }

    public function encryptCircleMsisdn($msisdn) {
        $res = $this->callCryptoApi('/encrypt-circle-msisdn', ['msisdn' => $msisdn]);
        return $res['encrypted_msisdn'] ?? null;
    }
    public function decryptCircleMsisdn($encrypted) {
        $res = $this->callCryptoApi('/decrypt-circle-msisdn', ['encrypted_msisdn' => $encrypted]);
        return $res['msisdn'] ?? null;
    }

    public function verifyApiKey($key) {
        $r = Http::timeout(10)->get($this->baseCryptoUrl.'/verify', ['key'=>$key]);
        if ($r->successful()) return $r->json();
        throw new \Exception("Server merespons: ".$r->status());
    }

    // ✅ FIX: Simpan fingerprint di storage agar konsisten antar request
    public function loadAxFp()
    {
        $filePath = 'ax.fp';
        $disk = Storage::disk('local');

        // Jika sudah ada file, baca dan kembalikan
        if ($disk->exists($filePath)) {
            $content = trim($disk->get($filePath));
            if (!empty($content)) {
                return $content;
            }
        }

        // Jika belum ada, generate baru seperti Python
        $dev = "samsung1337|SM-N931337|en|720x1540|GMT07:00|192.169.69.69|1.0|Android 13|6281398370564";
        $iv = str_repeat("\0", 16);
        $ct = openssl_encrypt($dev, 'aes-256-cbc', $this->axFpKey, OPENSSL_RAW_DATA, $iv);
        $newFp = base64_encode($ct);

        // Simpan ke storage/app/ax.fp
        $disk->put($filePath, $newFp);

        return $newFp;
    }

    public function axDeviceId() { return md5($this->loadAxFp()); }

    public function javaLikeTimestamp($carbonDate) {
        $ms2 = str_pad(floor($carbonDate->micro / 10000), 2, '0', STR_PAD_LEFT);
        return $carbonDate->format('Y-m-d\TH:i:s.') . $ms2 . $carbonDate->format('P');
    }

    public function tsGmt7WithoutColon($carbonDate) {
        $dt = $carbonDate->copy()->setTimezone('Asia/Jakarta');
        $millis = str_pad(floor($dt->micro / 1000), 3, '0', STR_PAD_LEFT);
        return $dt->format('Y-m-d\TH:i:s.') . $millis . $dt->format('O');
    }

    public function callCryptoApi($endpoint, $payload) {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) throw new \Exception("API Key kosong.");
        $r = Http::withHeaders(['Content-Type'=>'application/json','x-api-key'=>$apiKey])
            ->timeout(30)->post($this->baseCryptoUrl.$endpoint, $payload);
        if ($r->status()===402) throw new \Exception("Kredit API habis.");
        if (!$r->successful()) throw new \Exception("Crypto API Error: ".$r->body());
        return $r->json();
    }

    public function axApiSignature($tsForSign, $contact, $code, $contactType) {
        $res = $this->callCryptoApi('/sign-ax', [
            'ts_for_sign' => $tsForSign,
            'contact' => $contact,
            'code' => $code,
            'contact_type' => $contactType
        ]);
        return $res['ax_signature'];
    }

    public function encryptSignXData($method, $path, $idToken, $payload) {
        return $this->callCryptoApi('/encryptsign', [
            'id_token'=>$idToken, 'method'=>$method, 'path'=>$path, 'body'=>$payload
        ]);
    }

    public function decryptXData($encryptedPayload) {
        $res = $this->callCryptoApi('/decrypt', $encryptedPayload);
        return $res['plaintext'] ?? null;
    }

    public function buildEncryptedField() {
        $key = $this->aesKeyAscii ?: config('provider.aes_key');
        $iv = str_repeat("\0", 16);
        $ct = openssl_encrypt('', 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($ct) . bin2hex($iv);
    }
}
