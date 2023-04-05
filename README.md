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
    - Parameter 2: Amount in subunits(required).  for an amount of ₹295, enter 29500.
    - Parameter 3: Currency(required)
    - Parameter 4: Receipt(optional), 
    - Parameter 5: Notes(optional)

### Update Order Status
- Call updateOrderStatus function to update or get the latest order status
    - parameter 1: order Id(required)
    - Parameter 2: subscription Id(optional)
        - subscription ID required only for subscription, not for one-off payment

### Fetch an Order With id
- Call getOrderByOrderId function to fetch an order with razorpay order id.
    - parameter 1: order Id(required)

### Verify Payment
- After manual payment you will get razorpay_payment_id, razorpay_order_id, razorpay_signature
- Now call verifySignature function to verify payment with parameter
    - parameter 1: razorpay_order_id(required)
    - Parameter 2: razorpay_payment_id(required).
    - Parameter 3: razorpay_signature(required)

### Payment
- After successfully verifying payment, use the payment function to obtain the necessary information from Razorpay. 
    - parameter 1: razorpay_payment_id(required)
    - Add data in our plutus payment table

### Update Payment Status
- Call updatePaymentStatus function to update or get the latest payment status
    - parameter 1: payment Id(required)
    

### Fetch an Payment With id
- Call getPaymentByPaymentId function to fetch an payment with razorpay payment id.
    - parameter 1: payment Id(required) 

### Capture Payment
- Call capture_payment function to cature payment and update payment status
    - parameter 1: razorpay_payment_id(required)
    - parameter 3: amount(required)

### Plan
    A plan is a foundation on which a Subscription is built. It acts as a reusable template and contains details of the goods or services offered, the amount to be charged and the frequency at which the customer should be charged (billing cycle). Depending upon your business, you can create multiple plans with different billing cycles and pricing.
- Call the plan function to create a run-time plan.
    - parameter 1: period
        #### This, combined with interval, defines the frequency. Possible values:
        - daily
        - weekly
        - monthly
        - yearly

    - parameter 2: interval
        - (integer) This, combined with period, defines the frequency. If the billing cycle is 2 months, the value should be 2.
    - parameter 3: amount
    - parameter 4: notes
        - (object) Notes you can enter for the contact for future reference. This is a key-value pair. You can enter a maximum of 15 key-value pairs. For example, "note_key": "Beam me up Scotty”.
- Call the getPlan function to retrieves the details of a plan using its unique identifier.
    - parameter 1: razorpay_payment_id(required)


### Subscription
    You can use Subscriptions to charge a customer periodically. A Subscription ties a customer to a particular plan you have created. It contains details like the plan, the start date, total number of billing cycles, free trial period (if any) and upfront amount to be collected.
- Call the subscription function to create new subscription.
    - parameter 1: razorpay_payment_id(required)
    - parameter 2: date(optional)
        - If not passed, the Subscription starts immediately after the authorisation payment.
    - parameter 3: addons(optional)
        - Array that contains details of any upfront amount you want to collect as part of the authorisation transaction.
    - parameter 3: notes(optional)
        - (object) Notes you can enter for the contact for future reference. This is a key-value pair. You can enter a maximum of 15 key-value pairs. For example, "note_key": "Beam me up Scotty”.
    - parameter 4: offer_id(optional)
        - string The unique identifier of the offer that is linked to the Subscription. You can obtain this from the Dashboard.
- Call the updateSubscriptionStatus function to update/get the latest status of the subscription
    - parameter 1: razorpay_subscription_id(required)
- To stop auto-renawal call stopAutoRenewal function
    - parameter 1: razorpay_subscription_id(required)
    - parameter 2: cancelAtCycleEnd(required)
        - (boolean) Use this parameter to cancel a Subscription at the end of a billing cycle. Possible values:
            - 1: Cancel the subscription at the end of the current billing cycle.
- To cancel the subscription call cancel_subscription function 
    - parameter 1: razorpay_subscription_id(required)
    - parameter 2: cancelAtCycleEnd(required)
        - (boolean) Use this parameter to cancel a Subscription at the end of a billing cycle. Possible values:
            - 0 (default): Cancel the subscription immediately..





### Route/Fund Transfer
    Razorpay Route enables you to split payments received using the Razorpay Payment Gateway or other products (such as Payment Links, Payment Pages, Invoices, Subscriptions and Smart Collect) and transfer the funds to third parties, sellers or bank accounts.
- To transfer funds, call the transfer function.
    - parameter 1: account Id(required)
    - parameter 2: amount(required)