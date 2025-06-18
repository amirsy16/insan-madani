<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Redirect to admin panel
Route::redirect('/', '/admin');

// Custom password reset routes that bypass signature validation
Route::get('password/reset/{token}', [\App\Http\Controllers\Auth\NewPasswordController::class, 'create'])
    ->name('password.reset');

Route::post('password/reset', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])
    ->name('password.store');

Route::get('password/reset', function () {
    return redirect('/admin/password-reset');
})->name('password.request');


