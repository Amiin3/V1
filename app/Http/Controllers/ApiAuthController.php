<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\ProviderAuth;
use App\Services\CiamService;
use Inertia\Inertia;

class ApiAuthController extends Controller
{
    protected $authService;
    protected $ciamService;

    public function __construct(ProviderAuth $authService, CiamService $ciamService)
    {
        $this->authService = $authService;
        $this->ciamService = $ciamService;
    }

    // Halaman login reseller
    public function show()
    {
        return Inertia::render('Auth/ResellerLogin');
    }

    // Halaman login admin
    public function showAdmin()
    {
        return Inertia::render('Auth/AdminLogin');
    }

    // Login admin (username/password dari .env)
    public function loginAdmin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $adminUser = env('ADMIN_USERNAME', '1Hendrawati');
        $adminPass = env('ADMIN_PASSWORD', '1Hendrawati');

        if ($request->username === $adminUser && $request->password === $adminPass) {
            $this->authService->loginAndSaveUser($adminUser, [
                'access_token' => 'admin_token',
                'id_token' => 'admin_token',
                'refresh_token' => 'admin_token'
            ], 'admin');
            return redirect()->route('admin.resellers');
        }

        return back()->withErrors(['error' => 'Username atau password salah.']);
    }

    // Request OTP untuk reseller
    public function requestOtp(Request $request)
    {
        $request->validate(['number' => 'required|string']);
        try {
            $this->ciamService->requestOtp($request->number);
            return back()->with('success', 'OTP berhasil dikirim ke ' . $request->number)->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['number' => 'Gagal request OTP: ' . $e->getMessage()]);
        }
    }

    // Login reseller via OTP
    public function login(Request $request)
    {
        $request->validate(['number' => 'required|string', 'password' => 'required|string']);
        try {
            $tokens = $this->ciamService->submitOtp($request->number, $request->password, 'SMS');
            $this->authService->loginAndSaveUser($request->number, $tokens, 'reseller');
            return Inertia::render('Auth/ResellerLoginSuccess', ['user' => session('active_user')]);
        } catch (\Exception $e) {
            return back()->withErrors(['password' => 'OTP Salah/Expired: ' . $e->getMessage()]);
        }
    }

    // Logout
    public function logout()
    {
        $this->authService->logout();
        return redirect()->route('login');
    }
}
