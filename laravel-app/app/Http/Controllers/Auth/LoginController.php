<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BeyondAuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPhoneAuthController;
use App\Support\LoginQr;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('throttle:10,1')->only('login');
    }

    /**
     * Unified login UI (staff/admin first, then Beyond customer).
     * Auth::routes registers GET /login here — must not redirect to /login (loop).
     */
    public function showLoginForm()
    {
        return app(BeyondAuthController::class)->showLogin(request());
    }

    /**
     * Unified login submit — accepts legacy `name` or new `identifier`.
     */
    public function login(Request $request)
    {
        if (! $request->filled('identifier') && $request->filled('name')) {
            $request->merge(['identifier' => $request->input('name')]);
        }

        return app(BeyondAuthController::class)->login($request);
    }

    public function showLoginQr(Request $request)
    {
        $gate = $request->session()->get('staff_qr_gate');
        $user = $this->qrGateUser($gate);
        if (! $user) {
            return redirect('/login');
        }

        return view('beyond.auth.login_qr', [
            'code' => LoginQr::code($user),
            'qr' => LoginQr::imageDataUri($user),
        ]);
    }

    public function submitLoginQr(Request $request)
    {
        $gate = $request->session()->get('staff_qr_gate');
        $user = $this->qrGateUser($gate);
        if (! $user || ! LoginQr::matches($user, $request->input('login_qr'))) {
            return back()->withErrors(['login_qr' => 'Enter the code from the QR.']);
        }

        $next = is_array($gate) ? ($gate['next'] ?? 'staff') : 'staff';
        $request->session()->forget('staff_qr_gate');
        $request->session()->put('staff_qr_passed', (int) $user->id);

        if ($next === 'staff-phone') {
            return app(StaffPhoneAuthController::class)->finishAfterQr($request, $user);
        }

        return app(BeyondAuthController::class)->resumeStaffLogin($request, $user);
    }

    private function qrGateUser($gate)
    {
        $id = is_array($gate) ? (int) ($gate['user_id'] ?? 0) : 0;
        if ($id < 1) {
            return null;
        }

        return User::where('is_active', 1)->find($id);
    }

    public function sendOTP($phone)
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $result = app(\App\Services\Messaging\NotificationRouter::class)
            ->sendWhatsAppOtp($phone, $otp, 'login', 10);

        if (empty($result['success'])) {
            \Log::warning('[login-otp] send failed', [
                'phone' => $phone,
                'error' => $result['error'] ?? null,
                'provider' => $result['provider'] ?? null,
            ]);
            throw new \Exception($result['error'] ?? 'Failed to send WhatsApp OTP.');
        }

        return $otp;
    }

    /**
     * Admin/POS/intern logout. Clears both guards and destroys the session
     * so showLogin cannot bounce an intern back to their dashboard.
     */
    public function logout(Request $request)
    {
        return \App\Support\AuthLogout::perform($request);
    }
}
