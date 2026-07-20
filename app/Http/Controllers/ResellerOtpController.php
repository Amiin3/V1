<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResellerOtpController extends Controller
{
    public function getReadySessions()
    {
        if (!session('active_user')) return response()->json(['error' => 'Unauthorized'], 401);
        $sessions = [];
        if (Schema::hasTable('reseller_sessions')) {
            $sessions = DB::table('reseller_sessions')
                ->select('phone_number', 'session_data', 'updated_at')
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($item) {
                    $data = json_decode($item->session_data, true);
                    return [
                        'phone_number' => $item->phone_number,
                        'access_token' => $data['access_token'] ?? '',
                        'id_token' => $data['id_token'] ?? '',
                        'refresh_token' => $data['refresh_token'] ?? '',
                        'updated_at' => $item->updated_at,
                    ];
                });
        }
        return response()->json($sessions);
    }
}
