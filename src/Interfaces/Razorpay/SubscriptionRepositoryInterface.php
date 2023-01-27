<?php
namespace Arhamlabs\PaymentGateway\Interfaces\Razorpay;

interface SubscriptionRepositoryInterface
{
    public function checkSubscription($subscriptionId);
    public function createSubscription($planId, $subscriptionResponse);
}