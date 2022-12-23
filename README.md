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
