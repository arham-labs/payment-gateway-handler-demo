<?php
namespace Arhamlabs\PaymentGateway\Repositories\Razorpay;

use Illuminate\Support\Str;
use Arhamlabs\PaymentGateway\Models\PlutusSubscription;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\SubscriptionRepositoryInterface;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function checkSubscription($subscriptionId)
    {
        return PlutusSubscription::where('rzp_subscription_id', $subscriptionId)->exists();
    }

    public function createSubscription($planId, $subscriptionResponse)
    {
        PlutusSubscription::create([
            'uuid' => Str::uuid(),
            'rzp_plan_id' => $planId,
            'rzp_subscription_id' => $subscriptionResponse->id,
            'rzp_customer_id' => empty($subscriptionResponse->customer_id) ? null : $subscriptionResponse->customer_id,
            'status' => $subscriptionResponse->status,
            'quantity' => $subscriptionResponse->quantity,
            'notes' => json_encode($subscriptionResponse->notes),
            'charge_at_timestamp' => $subscriptionResponse->charge_at,
            'start_at_timestamp' => $subscriptionResponse->start_at,
            'end_at_timestamp' => $subscriptionResponse->end_at,
            'total_count' => $subscriptionResponse->total_count,
            'paid_count' => $subscriptionResponse->paid_count,
            'customer_notify' => $subscriptionResponse->customer_notify,
            'addons' => empty($subscriptionResponse->addons) ? null : json_encode($subscriptionResponse->addons),
            'created_at_timestamp' => $subscriptionResponse->created_at,
            'expire_by_timestamp' => $subscriptionResponse->expire_by,
            'has_scheduled_changes' => $subscriptionResponse->has_scheduled_changes,
            'remaining_count' => $subscriptionResponse->remaining_count,
            'source' => $subscriptionResponse->source
        ]);
    }
}