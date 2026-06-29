<?php

namespace App\Providers;

use App\Services\BackgroundRemovers\HttpBackgroundRemover;
use App\Services\BackgroundRemovers\NullBackgroundRemover;
use App\Services\Contracts\BackgroundRemover;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 去背驅動：依設定切換 null（不去背）或 http（自架 rembg / remove.bg）
        $this->app->bind(BackgroundRemover::class, function () {
            $cfg = config('beyblade.background_removal');

            if (($cfg['driver'] ?? 'null') === 'http' && ! empty($cfg['endpoint'])) {
                return new HttpBackgroundRemover(
                    $cfg['endpoint'],
                    $cfg['api_key'] ?? null,
                    (int) ($cfg['timeout'] ?? 30),
                );
            }

            return new NullBackgroundRemover();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
