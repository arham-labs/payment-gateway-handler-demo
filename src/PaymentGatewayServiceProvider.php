<?php
namespace Arhamlabs\PaymentGateway;

use Illuminate\Support\ServiceProvider;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/arhamlabs_pg.php' => config_path('arhamlabs_pg.php'),
        ]);
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/arhamlabs_pg.php','arhamlabs_pg'
        );
    }
}