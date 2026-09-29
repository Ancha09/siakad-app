<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Models\Pengumuman;
use App\Services\MidtransPaymentService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function ($app) {
            return match (config('payments.provider')) {
                'midtrans' => $app->make(MidtransPaymentService::class),
                default => throw new \RuntimeException('Provider pembayaran tidak didukung.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination.default');

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        View::composer(
            ['layouts.admin', 'layouts.dosen', 'layouts.mahasiswa', 'components.pengumuman-feed'],
            function ($view) {
                $user = auth()->user();
                if (! $user) {
                    return;
                }
                $query = Pengumuman::terlihat($user);
                $unreadCount = (clone $query)->whereDoesntHave('pembaca', fn ($q) => $q->where('users.id', $user->id))->count();
                $items = $query->denganStatusBaca($user)->orderByDesc('penting')->orderByDesc('terbit_pada')->orderByDesc('id')->limit(5)->get();
                $view->with(['notificationItems' => $items, 'unreadCount' => $unreadCount]);
            }
        );
    }
}
