<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // our token model: no abilities column
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
