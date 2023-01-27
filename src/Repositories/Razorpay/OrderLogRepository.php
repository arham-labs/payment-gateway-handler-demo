<?php
namespace Arhamlabs\PaymentGateway\Repositories\Razorpay;


use Illuminate\Support\Str;
use Arhamlabs\PaymentGateway\Models\PlutusOrderLog;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\OrderLogRepositoryInterface;


class OrderLogRepository implements OrderLogRepositoryInterface
{
    public function createOrderLog($logData)
    {
        $pgOrderLog = new PlutusOrderLog;
        $pgOrderLog->uuid = Str::uuid();
        $pgOrderLog->rzp_order_id = $logData['rzp_order_id'];
        $pgOrderLog->status = $logData['status'];
        $pgOrderLog->save();
        return $logData;
    }
}