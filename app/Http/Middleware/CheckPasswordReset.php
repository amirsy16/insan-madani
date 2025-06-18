<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordReset
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Check if user's password is null/empty (reset by super admin)
        if ($user && is_null($user->password)) {
            // Redirect to profile page to set new password
            if (!$request->routeIs('filament.admin.pages.my-profile')) {
                return redirect()->route('filament.admin.pages.my-profile')
                    ->with('password_reset_required', true);
            }
        }
        
        return $next($request);
    }
}
