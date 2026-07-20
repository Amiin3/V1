<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DecoyService
{
    public function getDecoy($paymentType, $prefix = 'default-')
    {
        $decoyName = $prefix . $paymentType;
        
        // Cache selama 300 detik (5 menit) persis seperti di Python
        return Cache::remember('decoy_' . $decoyName, 300, function () use ($decoyName) {
            try {
                $url = "https://me.mashu.lol/pg-decoy-{$decoyName}.json";
                $response = Http::timeout(30)->get($url);
                
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                Log::error("Gagal mengambil Decoy $decoyName: " . $e->getMessage());
            }
            return null;
        });
    }
}
