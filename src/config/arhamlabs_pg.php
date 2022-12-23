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
];