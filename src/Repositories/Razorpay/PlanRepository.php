<?php
namespace Arhamlabs\PaymentGateway\Repositories\Razorpay;


use Illuminate\Support\Str;
use Arhamlabs\PaymentGateway\Models\PlutusPlan;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PlanRepositoryInterface;



class PlanRepository implements PlanRepositoryInterface
{
    public function createPlan($planId, $period, $interval, $item, $notes, $createdAt)
    {
        $plan = new PlutusPlan;
        $plan->uuid = Str::uuid();
        $plan->user_id = 1;
        $plan->rzp_plan_id = $planId;
        $plan->period = $period;
        $plan->interval = $interval;
        $plan->item = json_encode($item);
        $plan->notes = json_encode($notes);
        $plan->rzp_created_at = $createdAt;
        return $plan->save();
    }
}