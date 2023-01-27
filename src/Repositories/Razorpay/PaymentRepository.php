<?php
namespace Arhamlabs\PaymentGateway\Repositories\Razorpay;

use Illuminate\Support\Str;
use Arhamlabs\PaymentGateway\traits\ApiCall;
use Arhamlabs\PaymentGateway\Models\PlutusPayment;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PaymentRepositoryInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function checkPaymentExists($orderId)
    {
        return PlutusPayment::where('rzp_order_id', $orderId)->exists();
    }

    public function getPaymentByPaymentId($paymentUrl, $paymentId, $encodeRazorKey)
    {
        // 127.0.0.1:8000/api/get-payment/pay_L7X8nvefjZgzkv
        return ApiCall::getCall($paymentUrl, $paymentId, $encodeRazorKey);
    }

    public function createPayment($paymentResult)
    {
        // dd($paymentResult);
        PlutusPayment::create([
            'uuid' => Str::uuid(),
            'rzp_order_id' => $paymentResult->order_id,
            'rzp_payment_id' => $paymentResult->payment_id,
            'amount' => $paymentResult->amount,
            'currency' => $paymentResult->currency,
            'status' => $paymentResult->status,
            'rzp_invoice_id' => $paymentResult->invoice_id,
            'international' => $paymentResult->international,
            'method' => $paymentResult->method,
            'amount_refunded' => $paymentResult->amount_refunded,
            'refund_status' => $paymentResult->refund_status,
            'captured' => $paymentResult->captured,
            'description' => $paymentResult->description,
            'card_id' => $paymentResult->card_id,
            'bank' => $paymentResult->bank,
            'wallet' => $paymentResult->wallet,
            'vpa' => $paymentResult->vpa,
            'email' => $paymentResult->email,
            'contact' => $paymentResult->contact,
            'rzp_token_id' => $paymentResult->token_id,
            'notes' => json_encode($paymentResult->notes),
            'fee' => $paymentResult->fee,
            'tax' => $paymentResult->tax,
            'error_code' => $paymentResult->error_code,
            'error_description' => $paymentResult->error_description,
            'error_source' => $paymentResult->error_source,
            'error_step' => $paymentResult->error_step,
            'error_reason' => $paymentResult->error_reason,
            'acquirer_data' => json_encode($paymentResult->acquirer_data),
            'rzp_created_at' => (string) $paymentResult->created_at,
        ]);
    }
}