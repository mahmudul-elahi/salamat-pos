<?php

namespace App\Providers;


use App\Models\Ecommerce\Order\Order;
use App\Observers\OrderObserver;
use App\Services\Setting\SettingService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        Order::observe(OrderObserver::class);

        Schema::defaultStringLength(191);

        try {
            $settings = resolve(SettingService::class)->getFormattedSettings();
            View::composer('*', function ($view) use ($settings) {
                $view->with('settings', $settings);
            });

            foreach ($settings as $key => $setting) {
                if ($key == 'company_name') {
                    config()->set('app.name', $setting);
                }
                config()->set('settings.application.' . $key, $setting);
            }
        } catch (\Exception $exception) {
        }
    }
}
