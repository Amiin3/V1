<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\CircleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\BalanceAllotmentController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ValidateController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\HotController;
use App\Http\Controllers\PaymentController;

// ============================================================
// HALAMAN LOGIN
// ============================================================
Route::get('/login', fn() => Inertia::render('Auth/ResellerLogin'))->name('login');
Route::get('/admin/login', fn() => view('admin-login'))->name('login.admin');
Route::post('/admin/login', [ApiAuthController::class, 'loginAdmin'])->name('login.admin.submit');

// ============================================================
// OTENTIKASI RESELLER (OTP)
// ============================================================
Route::post('/login/request-otp', [ApiAuthController::class, 'requestOtp'])->name('login.request-otp');
Route::post('/login', [ApiAuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout');

Route::post('/login/auto', function (Request $request) {
    $phone = $request->number;
    $auth = app(\App\Services\ProviderAuth::class);

    if (Schema::hasTable('reseller_sessions')) {
        $session = DB::table('reseller_sessions')->where('phone_number', $phone)->first();
        if ($session && !empty($session->session_data)) {
            $data = json_decode($session->session_data, true);
            $refreshToken = $data['refresh_token'] ?? '';

            // Coba dapatkan token baru dari refresh_token
            if (!empty($refreshToken)) {
                $tokens = $auth->getNewToken($refreshToken);
                if ($tokens && !empty($tokens['id_token'])) {
                    $auth->loginAndSaveUser($phone, $tokens, 'reseller');
                    return redirect('/dashboard');
                }
            }

            // Fallback: gunakan token yang ada jika masih valid
            if (!empty($data['id_token'])) {
                $tokens = [
                    'access_token' => $data['access_token'] ?? '',
                    'id_token' => $data['id_token'],
                    'refresh_token' => $data['refresh_token'] ?? '',
                ];
                $auth->loginAndSaveUser($phone, $tokens, 'reseller');
                return redirect('/dashboard');
            }
        }
    }

    return back()->with('error', 'Token tidak valid. Silakan login ulang dengan OTP.');
})->name('login.auto');

// ============================================================
// AUTO LOGIN DARI SESI TERSIMPAN
// ============================================================

// ============================================================
// ADMIN – DAFTAR RESELLER (BLADE VIEW)
// ============================================================
Route::get('/admin/resellers', function () {
    $user = session('active_user');
    if (!$user) return redirect('/login');

    $sessions = [];
    if (Schema::hasTable('reseller_sessions')) {
        $sessions = DB::table('reseller_sessions')
            ->select('phone_number', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    return view('reseller-list', [
        'sessions' => $sessions
    ]);
})->name('admin.resellers');

// ============================================================
// RESELLER – DASHBOARD MODERN (VUE)
// ============================================================
Route::get('/reseller', function () {
    $user = session('active_user');
    if (!$user) return redirect('/login');
    return Inertia::render('Reseller/Dashboard', [
        'user' => $user
    ]);
})->name('reseller.dashboard');

Route::get('/reseller/hot', [HotController::class, 'index'])->name('reseller.hot');
Route::post('/reseller/buy', [PaymentController::class, 'buy'])->name('reseller.buy');

// ============================================================
// DASHBOARD ADMIN (MENU LENGKAP)
// ============================================================
Route::get('/', function () {
    if (!session('active_user')) return redirect('/login');
    return redirect('/admin/resellers');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/packages/my', [PackageController::class, 'myPackages'])->name('packages.my');
Route::get('/transaction-history', [TransactionController::class, 'index'])->name('transactions.history');
Route::get('/family', [FamilyController::class, 'index'])->name('family');
Route::get('/circle', [CircleController::class, 'index'])->name('circle');
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
Route::get('/store/segments', [StoreController::class, 'segments'])->name('store.segments');
Route::get('/store/families', [StoreController::class, 'families'])->name('store.families');
Route::get('/store/packages', [StoreController::class, 'packages'])->name('store.packages');
Route::get('/store/redeemables', [StoreController::class, 'redeemables'])->name('store.redeemables');
Route::get('/balance-allotment', [BalanceAllotmentController::class, 'index'])->name('balance.allotment');
Route::get('/register', [RegisterController::class, 'index'])->name('register');
Route::get('/validate', [ValidateController::class, 'index'])->name('validate');
Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks');
Route::get('/packages/hot', [HotController::class, 'index'])->name('packages.hot');
Route::get('/packages/hot2', [HotController::class, 'hot2Index'])->name('packages.hot2');
Route::get('/packages/detail/{familyCode}/{variantName}/{order}', [HotController::class, 'detail'])->name('packages.detail');
Route::get('/packages/detail-by-option/{optionCode}', [HotController::class, 'detailByOptionCode'])->name('packages.detail.by.option');
Route::post('/packages/family-list', [HotController::class, 'familyPackages'])->name('packages.familyList');
Route::post('/packages/buy', [PaymentController::class, 'buy'])->name('packages.buy');
Route::post('/packages/hot2/buy', [PaymentController::class, 'buyMultiple'])->name('packages.buyMultiple');
Route::get('/packages/option', fn() => Inertia::render('Hot/OptionCode'))->name('packages.option');
Route::get('/packages/family', fn() => Inertia::render('Hot/FamilyCode'))->name('packages.family');

// Debug
Route::get('/debug-session', function () {
    return response()->json([
        'user' => session('active_user'),
        'session_id' => session()->getId(),
        'all' => session()->all(),
    ]);
})->name('debug.session');
Route::get('/packages/loop', fn() => Inertia::render('Hot/LoopPurchase'))->name('packages.loop');
