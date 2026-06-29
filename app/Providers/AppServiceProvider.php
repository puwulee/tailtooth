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
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * 驅動設定優先序：後台 settings（管理者輸入）> config/env。
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);

        // 去背驅動：null（不去背）或 http（自架 rembg / remove.bg）
        $this->app->bind(BackgroundRemover::class, function ($app) {
            $s = $app->make(SettingsService::class);
            $driver = $s->get('bg.driver', config('beyblade.background_removal.driver', 'null'));
            $endpoint = $s->get('bg.endpoint', config('beyblade.background_removal.endpoint'));

            if ($driver === 'http' && ! empty($endpoint)) {
                return new HttpBackgroundRemover(
                    $endpoint,
                    $s->get('bg.api_key', config('beyblade.background_removal.api_key')),
                    (int) config('beyblade.background_removal.timeout', 30),
                );
            }

            return new NullBackgroundRemover();
        });

        // 金流：manual（臨櫃/開發）或 linepay
        $this->app->bind(PaymentGateway::class, function ($app) {
            $s = $app->make(SettingsService::class);
            $driver = $s->get('payment.driver', config('billing.payment.driver', 'manual'));

            if ($driver === 'linepay') {
                return new LinePayGateway(
                    $s->get('linepay.channel_id', config('billing.payment.linepay.channel_id')),
                    $s->get('linepay.channel_secret', config('billing.payment.linepay.channel_secret')),
                    $s->get('linepay.base_url', config('billing.payment.linepay.base_url')),
                    $s->get('linepay.confirm_url', config('billing.payment.linepay.confirm_url')),
                    $s->get('linepay.cancel_url', config('billing.payment.linepay.cancel_url')),
                );
            }

            return new ManualPaymentGateway();
        });

        // 電子發票：null（開發）或 guangmao（光貿）
        $this->app->bind(InvoiceIssuer::class, function ($app) {
            $s = $app->make(SettingsService::class);
            $driver = $s->get('invoice.driver', config('billing.invoice.driver', 'null'));

            if ($driver === 'guangmao') {
                return new GuangMaoInvoiceIssuer(
                    $s->get('invoice.endpoint', config('billing.invoice.guangmao.endpoint')),
                    $s->get('invoice.merchant_id', config('billing.invoice.guangmao.merchant_id')),
                    $s->get('invoice.api_key', config('billing.invoice.guangmao.api_key')),
                );
            }

            return new NullInvoiceIssuer();
        });
    }

    public function boot(): void
    {
        //
    }
}
