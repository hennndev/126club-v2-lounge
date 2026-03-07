<?php

namespace App\Providers;

use App\Models\DisplayMessageRequest;
use App\Models\SongRequest;
use App\Models\TableReservation;
use App\Services\AccurateService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AccurateService::class, function ($app) {
            return new AccurateService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.sidebar', function ($view) {
            $view->with('pendingBookingsCount', TableReservation::where('status', ['confirmed', 'pending'])->count());
            $view->with('pendingSongRequestsCount', SongRequest::where('status', 'pending')->count());
            $view->with('pendingDisplayMessagesCount', DisplayMessageRequest::where('status', 'pending')->count());
        });
    }
}
