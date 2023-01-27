<?php
namespace Arhamlabs\PaymentGateway\Interfaces\Razorpay;

interface PaymentRepositoryInterface
{
    public function checkPaymentExists($orderId);
    public function getPaymentByPaymentId($paymentUrl, $paymentId, $encodeRazorKey);

    public function createPayment($paymentResult);
}