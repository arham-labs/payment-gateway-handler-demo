<?php
namespace Arhamlabs\PaymentGateway\Interfaces\Razorpay;


interface OrderRepositoryInterface
{
    public function getOrderByPaymentId($paymentUrl, $paymentId, $encodeRazorKey);
    public function createOrder($data);

    public function updateOrder($orderId, $orderResponse);

    public function checkOrderById($orderId);
}