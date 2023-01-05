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

### Verify Payment
- After manual payment you will get razorpay_payment_id, razorpay_order_id, razorpay_signature
- Now call verifySignature function to verify payment with parameter
- parameter 1: razorpay_order_id(required)
- Parameter 2: razorpay_payment_id(required).
- Parameter 3: razorpay_signature(required)

### Payment
- After successfully verifying payment, use the payment function to obtain the necessary information from Razorpay. 
- parameter 1: razorpay_payment_id(required)
- Add data in our payment table

### Capture Payment
- Call capture_payment function to cature payment and update payment status
- parameter 1: razorpay_payment_id(required)
- parameter 3: amount(required)

### Plan

### Subscription

### Route/Fund Transfer

### Update Payment