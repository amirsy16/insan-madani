<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Redirect to admin panel
Route::redirect('/home', '/admin');

// Custom password reset routes that bypass signature validation
Route::get('password/reset/{token}', function ($token) {
    // Extract email from request if available
    $email = request('email');
    
    // Build query parameters for Filament
    $query = http_build_query([
        'token' => $token,
        'email' => $email
    ]);
    
    return redirect('/admin/password-reset/reset?' . $query);
})->name('password.reset');

Route::get('password/reset', function () {
    return redirect('/admin/password-reset');
})->name('password.request');


