<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Find the user
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return back()->withInput($request->only('email'))
                        ->withErrors(['email' => 'User dengan email tersebut tidak ditemukan.']);
        }

        // Check if token exists and is valid
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenData) {
            return back()->withInput($request->only('email'))
                        ->withErrors(['email' => 'Token reset password tidak ditemukan.']);
        }

        // Check if token matches (compare with SHA256 hash)
        if (hash('sha256', $request->token) !== $tokenData->token) {
            return back()->withInput($request->only('email'))
                        ->withErrors(['email' => 'Token reset password tidak valid.']);
        }

        // Check if token has expired (60 minutes)
        if (now()->diffInMinutes($tokenData->created_at) > 60) {
            // Delete expired token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            
            return back()->withInput($request->only('email'))
                        ->withErrors(['email' => 'Token reset password telah kedaluwarsa.']);
        }

        // Reset the password
        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        // Delete the used token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Fire password reset event
        event(new PasswordReset($user));

        return redirect()->route('filament.admin.auth.login')
                        ->with('status', 'Password berhasil direset! Silakan login dengan password baru Anda.');
    }
}
