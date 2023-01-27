<?php
namespace Arhamlabs\PaymentGateway\Interfaces\Razorpay;

interface PlanRepositoryInterface
{
    public function createPlan($planId, $period, $interval, $item, $notes, $createdAt);
}