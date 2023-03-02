<?php

return [
    'errors' => [
        'boolean' => 'must be a boolean value.',
        'integer' => 'must be a integer value.'
    ],
    'active_mode' => env('ACTIVE_MODE'),
    'razorpay_test_id' => env('RAZORPAY_TEST_ID'),
    'razorpay_test_secret' => env('RAZORPAY_TEST_SECRET'),
    'razorpay_live_id' => env('RAZORPAY_LIVE_ID'),
    'razorpay_live_secret' => env('RAZORPAY_LIVE_SECRET'),
    'allow_capture_payment' => env('ALLOW_CAPTURE_PAYMENT'),
    'allow_subscription' => env('ALLOW_SUBSCRIPTION'),
    'allow_future_subscription_payment' => env('ALLOW_FUTURE_SUBSCRIPTION_PAYMENT'),
    'stop_auto_renewal' => env('STOP_AUTO_RENEWAL'),
    'allow_refund' => env('ALLOW_REFUND'),
    'transfer_fund' => env('TRANSFER_FUND'),
    'account_id' => env('ACCOUNT_ID'),
];