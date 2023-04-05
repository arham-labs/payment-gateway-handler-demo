<?php
namespace Arhamlabs\PaymentGateway\Repositories\Razorpay;

use Arhamlabs\PaymentGateway\traits\ApiCall;
use Illuminate\Support\Str;
use Arhamlabs\PaymentGateway\Models\PlutusOrder;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\OrderRepositoryInterface;


class OrderRepository implements OrderRepositoryInterface
{
    public function getOrderByPaymentId($paymentUrl, $paymentId, $encodeRazorKey)
    {
        // 127.0.0.1:8000/api/get-payment/pay_L7X8nvefjZgzkv
        return ApiCall::getCall($paymentUrl, $paymentId, $encodeRazorKey);
    }

    public function getOrderByOrderId($orderUrl, $orderId, $encodeRazorKey)
    {
        // 127.0.0.1:8000/api/get-order/pay_L7X8nvefjZgzkv
        return ApiCall::getCall($orderUrl, $orderId, $encodeRazorKey);
    }
    public function createOrder($orderData)
    {
        $order = new PlutusOrder;
        $order->uuid = Str::uuid();
        $order->user_id = $orderData['user_id'];
        $order->order_id = empty($orderData['order_id']) ? Str::upper(Str::random(15)) : $orderData['order_id'];
        $order->rzp_subscription_id = empty($orderData['rzp_subscription_id']) ? null : $orderData['rzp_subscription_id'];
        $order->rzp_order_id = $orderData['rzp_order_id'];
        $order->amount = $orderData['amount'];
        $order->currency = $orderData['currency'];
        $order->receipt = $orderData['receipt'];
        $order->notes = json_encode($orderData['notes']);
        $order->status = $orderData['status'];
        $order->save();
        return $order;
    }

    public function updateOrder($orderId, $orderResponse)
    {
        $order = PlutusOrder::findOrFail($orderId);
        $order->rzp_order_id = $orderResponse->id;
        $order->rzp_offer_id = $orderResponse->offer_id;
        $order->status = $orderResponse->status;
        $order->rzp_created_at = $orderResponse->created_at;
        $order->save();
    }

    public function checkOrderById($orderId)
    {
        return PlutusOrder::select('id')->where('rzp_order_id', $orderId)->exists();
    }
}