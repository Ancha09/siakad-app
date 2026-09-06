<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        if (config('app.force_https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\View::composer(
            ['layouts.admin', 'layouts.dosen', 'layouts.mahasiswa', 'components.pengumuman-feed'],
            function ($view) {
                $user = auth()->user();
                if (! $user) return;
                $query = \App\Models\Pengumuman::terlihat($user);
                $unreadCount = (clone $query)->whereDoesntHave('pembaca', fn ($q) => $q->where('users.id', $user->id))->count();
                $items = $query->denganStatusBaca($user)->orderByDesc('penting')->orderByDesc('terbit_pada')->orderByDesc('id')->limit(5)->get();
                $view->with(['notificationItems' => $items, 'unreadCount' => $unreadCount]);
            }
        );
    }
}
