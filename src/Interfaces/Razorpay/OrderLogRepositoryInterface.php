<?php
namespace Arhamlabs\PaymentGateway\Interfaces\Razorpay;


interface OrderLogRepositoryInterface
{
    public function createOrderLog($data);
}