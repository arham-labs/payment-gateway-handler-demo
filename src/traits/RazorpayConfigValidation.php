<?php
namespace Arhamlabs\PaymentGateway\traits;

use Exception;
use Illuminate\Http\Response;
use Arhamlabs\ApiResponse\ApiResponse;
use Illuminate\Support\Facades\Validator;


trait RazorpayConfigValidation
{
    public $apiResponse;
    public function __construct(ApiResponse $apiResponse)
    {
        $this->$apiResponse = $apiResponse;
    }
    public function activeModeValidation()
    {
        try {
            $messages = [
                'active_mode.required' => 'Please set active mode in your .env file'
            ];


            $response = Validator::make([
                'active_mode' => config('arhamlabs_pg.active_mode')
            ], [
                    'active_mode' => "required"
                ], $messages);


        } catch (Exception $e) {
            $response = $this->apiResponse->getResponse($e->getCode(), array(), $e->getMessage());
        }
        return $response;
    }

    public function credentialsValidation($mode)
    {
        $messages = [
            'razorpay_' . $mode . '_id.required' => 'Please set razorpay ' . $mode . ' id in your .env file',
            'razorpay_' . $mode . '_secret.required' => 'Please set razorpay secret in your .env file'
        ];

        return Validator::make([
            'razorpay_' . $mode . '_id' => config('arhamlabs_pg.razorpay_' . $mode . '_id'),
            'razorpay_' . $mode . '_secret' => config('arhamlabs_pg.razorpay_' . $mode . '_secret'),

        ], [
                "razorpay_" . $mode . "_id" => "bail|required:razorpay_test_id",
                "razorpay_" . $mode . "_secret" => "bail|required:razorpay_test_secret"
            ], $messages);
    }

    public function paymentCaptureValidation()
    {
        $messages = [
            'allow_capture_payment.required' => 'Please set allow_capture_payment in your .env file',
        ];

        return Validator::make([
            'allow_capture_payment' => config('arhamlabs_pg.allow_capture_payment'),


        ], [
                "allow_capture_payment" => "bail|required:allow_capture_payment",
            ], $messages);
    }

    public function futureSubscriptionValidation()
    {
        $messages = [
            'allow_future_subscription_payment.required' => 'Please set allow_future_subscription_payment in your .env file'
        ];

        return Validator::make([
            'allow_future_subscription_payment' => config('arhamlabs_pg.allow_future_subscription_payment')

        ], [
                "allow_future_subscription_payment" => "bail|required:allow_future_subscription_payment",
            ], $messages);
    }


}