<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        // if(config('app.env') === 'local')
        // {
        //     URL::forceScheme('https');
            
        //     // Handle ngrok URLs dynamically
        //     if (request()->hasHeader('x-forwarded-host')) {
        //         $host = request()->header('x-forwarded-host');
        //         URL::forceRootUrl('https://' . $host);
        //     }
        // }

        
    }
}
