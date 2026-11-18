<?php

namespace App\Http\Controllers;

use App\Mail\OtpVerificationMail;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\BrevoMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->onlyInput('email');
        }

        if (!$user->isStaff() && is_null($user->email_verified_at)) {
            $this->generateAndSendOtp($user);
            session(['pending_verification_user_id' => $user->id]);

            return redirect()->route('otp.notice')
                ->with('success', 'Please verify your email first. We sent a fresh 6-digit code to ' . $user->email . '.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->isStaff()) {
            return redirect()->intended(route('staff.dashboard'));
        }

        return redirect()->intended(route('store.index'))
            ->with('show_rules_modal', true);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'agree_terms' => ['accepted'],
        ], [
            'agree_terms.accepted' => 'You must read and agree to the Terms and Regulation of Kohan Mart before registering.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'birthday' => $validated['birthday'] ?? null,
            'password' => $validated['password'],
            'role' => 'customer',
            'points_balance' => 0,
        ]);

        $this->generateAndSendOtp($user);
        session(['pending_verification_user_id' => $user->id]);

        return redirect()->route('otp.notice')
            ->with('success', 'We sent a 6-digit verification code to ' . $user->email . '.');
    }

    public function showOtpForm()
    {
        $userId = session('pending_verification_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            session()->forget('pending_verification_user_id');
            return redirect()->route('register');
        }

        if (!is_null($user->email_verified_at)) {
            session()->forget('pending_verification_user_id');
            return redirect()->route('login')->with('success', 'Your email is already verified. Please sign in.');
        }

        return view('auth.verify-otp', compact('user'));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => ['required', 'digits:6'],
        ]);

        $userId = session('pending_verification_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('login')->with('error', 'Verification session expired. Please sign in again.');
        }

        if (!$user->otp_code || $user->otp_code !== $request->otp_code) {
            return back()->withErrors(['otp_code' => 'Invalid 6-digit verification code. Please double-check your email.']);
        }

        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            return back()->withErrors(['otp_code' => 'That verification code has expired. Click "Resend Code" for a new one.']);
        }

        $welcomeBonus = 20.00;

        $user->update([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'points_balance' => $user->points_balance + $welcomeBonus,
        ]);

        PointTransaction::create([
            'user_id' => $user->id,
            'order_id' => null,
            'type' => 'welcome_bonus',
            'points' => $welcomeBonus,
            'description' => 'Welcome bonus for verifying your Kohan Mart account',
        ]);

        session()->forget('pending_verification_user_id');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile.index')
            ->with('success', 'Email verified! +20 Welcome Points (₱20.00 value) have been added to your wallet.')
            ->with('show_rules_modal', true);
    }

    public function resendOtp()
    {
        $userId = session('pending_verification_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('login');
        }

        $this->generateAndSendOtp($user);

        return back()->with('success', 'A new 6-digit verification code has been sent to ' . $user->email . '.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been signed out.');
    }

    private function generateAndSendOtp(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->update([
            'otp_code' => $code,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $sent = BrevoMailer::sendMailable(
            $user->email,
            $user->name,
            new OtpVerificationMail($user->name, $code)
        );

        if (!$sent) {
            Log::warning("OTP email failed via Brevo for {$user->email}");
            session()->flash('error', 'We could not deliver the verification email right now. Please click "Resend Code" in a moment.');
        }
    }
}