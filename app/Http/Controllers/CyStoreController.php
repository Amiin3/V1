<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Carbon\Carbon;

class CyStoreController extends Controller
{
    private function getAuthHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . session('id_token'),
            'x-api-key' => env('ENGSEL_API_KEY'),
        ];
    }

    public function myPackages()
    {
        $response = Http::withHeaders($this->getAuthHeaders())
            ->post('https://api.domainlu.com/api/v8/packages/quota-details', [
                "is_enterprise" => false,
                "lang" => "en",
                "family_member_id" => ""
            ]);

        $quotas = [];
        if ($response->successful() && $response->json('status') === 'SUCCESS') {
            $quotas = $response->json('data.quotas') ?? [];
        }

        return Inertia::render('CyStore/MyPackages', [
            'quotas' => $quotas
        ]);
    }

    public function transactionHistory()
    {
        $response = Http::withHeaders($this->getAuthHeaders())
            ->get('https://api.domainlu.com/api/v1/transaction/history');

        $history = collect();
        if ($response->successful()) {
            $history = collect($response->json('list'))->map(function ($item) {
                $item['formatted_time'] = Carbon::createFromTimestamp($item['timestamp'])
                                            ->subHours(7)
                                            ->format('d F Y | H:i \W\I\B');
                return $item;
            });
        }

        return Inertia::render('CyStore/TransactionHistory', [
            'history' => $history
        ]);
    }

    public function unsubscribe(Request $request)
    {
        $response = Http::withHeaders($this->getAuthHeaders())
            ->post('https://api.domainlu.com/api/v1/unsubscribe', [
                'quota_code' => $request->quota_code,
                'subscription_type' => $request->product_subscription_type,
                'domain' => $request->product_domain
            ]);

        if ($response->successful()) {
            return back()->with('success', 'Berhasil berhenti berlangganan.');
        }

        return back()->with('error', 'Gagal berhenti berlangganan.');
    }
}
