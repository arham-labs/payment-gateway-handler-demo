<?php
namespace Arhamlabs\PaymentGateway;

use Illuminate\Support\ServiceProvider;
use Arhamlabs\PaymentGateway\Repositories\Razorpay\PlanRepository;
use Arhamlabs\PaymentGateway\Repositories\Razorpay\OrderRepository;
use Arhamlabs\PaymentGateway\Repositories\Razorpay\PaymentRepository;
use Arhamlabs\PaymentGateway\Repositories\Razorpay\OrderLogRepository;
use Arhamlabs\PaymentGateway\Repositories\Razorpay\PaymentLogRepository;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PlanRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\OrderRepositoryInterface;
use Arhamlabs\PaymentGateway\Repositories\Razorpay\SubscriptionRepository;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PaymentRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\OrderLogRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PaymentLogRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\SubscriptionRepositoryInterface;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/arhamlabs_pg.php' => config_path('arhamlabs_pg.php'),
        ]);
        $this->publishes([
            __DIR__ . '/database/migrations/' => database_path('/migrations')
        ]);
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        // $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/arhamlabs_pg.php',
            'arhamlabs_pg'
        );

        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderLogRepositoryInterface::class, OrderLogRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(PaymentLogRepositoryInterface::class, PaymentLogRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
    }


}