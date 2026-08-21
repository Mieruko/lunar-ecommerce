<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Shipment;
use App\Models\WarrantyClaim;
use App\Observers\OrderObserver;
use App\Observers\ReturnRequestObserver;
use App\Observers\ShipmentObserver;
use App\Observers\WarrantyClaimObserver;
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
        Shipment::observe(ShipmentObserver::class);
        ReturnRequest::observe(ReturnRequestObserver::class);
        WarrantyClaim::observe(WarrantyClaimObserver::class);
    }
}
