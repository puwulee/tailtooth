<?php

namespace App\Providers;

use App\Services\BackgroundRemovers\HttpBackgroundRemover;
use App\Services\BackgroundRemovers\NullBackgroundRemover;
use App\Services\Contracts\BackgroundRemover;
use App\Services\Contracts\InvoiceIssuer;
use App\Services\Contracts\PaymentGateway;
use App\Services\Invoice\GuangMaoInvoiceIssuer;
use App\Services\Invoice\NullInvoiceIssuer;
use App\Services\Payment\LinePayGateway;
use App\Services\Payment\ManualPaymentGateway;
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

        // 金流：manual（臨櫃/開發）或 linepay
        $this->app->bind(PaymentGateway::class, function () {
            $cfg = config('billing.payment');
            if (($cfg['driver'] ?? 'manual') === 'linepay') {
                $lp = $cfg['linepay'];
                return new LinePayGateway($lp['channel_id'], $lp['channel_secret'], $lp['base_url'], $lp['confirm_url'], $lp['cancel_url']);
            }

            return new ManualPaymentGateway();
        });

        // 電子發票：null（開發）或 guangmao（光貿）
        $this->app->bind(InvoiceIssuer::class, function () {
            $cfg = config('billing.invoice');
            if (($cfg['driver'] ?? 'null') === 'guangmao') {
                $g = $cfg['guangmao'];
                return new GuangMaoInvoiceIssuer($g['endpoint'], $g['merchant_id'], $g['api_key']);
            }

            return new NullInvoiceIssuer();
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
