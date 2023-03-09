# Arhamlabs Payment Gateway

## Razorpay Payment Gateway Configuration

### Copy below code and paste it into your .env file

### Every time you make changes to your env file, please run php artisan optimize.

- ACTIVE_MODE = "TEST_OR_LIVE" // test/live

- RAZORPAY_TEST_ID = "YOUR_TEST_ID"
- RAZORPAY_TEST_secret = "YOUR_TEST_SECRET"
	
- RAZORPAY_LIVE_id = "YOUR_LIVE_ID"
- RAZORPAY_LIVE_SECRET = "YOUR_LIVE_SECRET"

- RAZORPAY_TEST_ACCOUNT_ID = "YOUR_TEST_ACCOUNT_ID" // For transer fund

- RAZORPAY_LIVE_ACCOUNT_ID = "YOUR_LIVE_ACCOUNT_ID" // For transer fund
	
- ALLOW_CREATE_CUSTOMER = // Create customer on razorpay(true/false)
	
- ALLOW_FUND_TRANSFER = // (true/false)
	
- ALLOW_CAPTURE_PAYMENT = // (true/false)

- ALLOW_SUBSCRIPTION = // (true/false)

- ALLOW_FUTURE_SUBSCRIPTION_PAYMENT = // (true/false)

### Create Order
- Call createOrder function to create order with parameter
- parameter 1: User Id(required)
- Parameter 2: SubscriptionId(Which is null in one off payment)
- Parameter 3: Amount in subunits(required).  for an amount of ₹295, enter 29500.
- Parameter 4: Currency(required)
- Parameter 5: Receipt(optional), 
- Parameter 6: Notes(optional)
- Parameter 7: Order Id(required) // Pass project order id here
### Note: Order Id should be alphanumeric. eg: 'AL' . Str::upper(Str::random(13))

### Verify Payment
- After manual payment you will get razorpay_payment_id, razorpay_order_id, razorpay_signature
- Now call verifySignature function to verify payment with parameter
- parameter 1: razorpay_order_id(required)
- Parameter 2: razorpay_payment_id(required).
- Parameter 3: razorpay_signature(required)

### Payment
- After successfully verifying payment, use the payment function to obtain the necessary information from Razorpay. 
- parameter 1: razorpay_payment_id(required)
- parameter 2: razorpay_subscription_id(required in case of subscription)
- Add data in our payments table

### Capture Payment
- Call capture_payment function to cature payment and update payment status
- parameter 1: razorpay_payment_id(required)
- parameter 2: amount(required)
- parameter 3: orderId(required)




### Check Plan
- function checkPlan($planId)
- parameter 1: razorpay_plan_id(required)
- To check plan exists or not

### Create PrePlan($planId)
- To create or get pre-plan data call createPrePlan($planId)
- parameter 1: razorpay_plan_id(required)
- Add plan data in plutus plan table if pre-plan not exists and return plan data from razorpay

### Plan
- To create run-time plan call plan($period, $interval, $amount, $notes)
- parameter 1: period(required)
- parameter 2: interval(required)
- parameter 3: amount(required)
- parameter 4: notes(optional)

### Subscription
- To create subscription call subscription($planId, $date, $addons, $notes, $offerId) function
- parameter 1: planId(required)
- parameter 2: date(optional if not passed current date will be consider)
- parameter 3: addons(optional)
- parameter 4: notes(optional)
- parameter 5: offerId(optional)


### Update Subscription Status
- To update subscription status call updateSubscriptionStatus($subscriptionId) function
- parameter 1: subscriptionId(required)
- To update subscription status in plutus_subscriptions table


### Stop Auto Renewal
- To stop auto-renewal call stopAutoRenewal($subscriptionId, $cancelAtCycleEnd) function
- parameter 1: subscriptionId(required)
- parameter 2: cancelAtCycleEnd(required)
### Note: pass 1 to cancel the subscription at the end of the current billing cycle.

### Refund payment
- To refund payment call refund($paymentId, $amount, $notes)
- parameter 1: paymentId(required)
- parameter 2: amount(required)
- parameter 3: notes(optional)


### Cancel Subscription($subscriptionId, $cancelAtCycleEnd)
- To cancel subscription call cancel_subscription($subscriptionId, $cancelAtCycleEnd) function
- parameter 1: subscriptionId (required)
- parameter 2: cancelAtCycleEnd (required)
### Note: pass 0 to cancel the subscription immediately.



### Route/Fund Transfer
- To transfer fund in other account call transfer($accountId, $amount) function
- parameter 1: accountId (required)
- parameter 2: amount (required)

### Update Order Status
- parameter 1: razorpay_order_id(required)
- Parameter 2: razorpay_subscription_id(required in case of subscription).
- To update order status in orders table

### Get Order By OrderId
- parameter 1: razorpay_order_id(required)
- Return orders table data based on razorpay order id

### Get Payment By PaymentId
- parameter 1: razorpay_payment_id(required)
- Return payments table data based on razorpay payment id

### Update Payment Status
- parameter 1: razorpay_payment_id(required)
- To update payment status in payments table