<?php
namespace Arhamlabs\PaymentGateway\traits;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
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

    public function stopAutoRenewalValidation()
    {
        $messages = [
            'stop_auto_renewal.required' => 'Please set stop_auto_renewal in your .env file'
        ];

        return Validator::make([
            'stop_auto_renewal' => config('arhamlabs_pg.stop_auto_renewal')

        ], [
                "stop_auto_renewal" => "bail|required:stop_auto_renewal",
            ], $messages);
    }

    public function allowRefundValidation()
    {
        $messages = [
            'allow_refund.required' => 'Please set allow_refund in your .env file'
        ];

        return Validator::make([
            'allow_refund' => config('arhamlabs_pg.allow_refund')

        ], [
                "allow_refund" => "bail|required:allow_refund",
            ], $messages);
    }


    public function validation()
	{
		$response = '';
		try {
			if ($this->activeModeValidation()->fails()) {
				throw new Exception(
					$this->activeModeValidation()
						->errors()
						->first(),
						Response::HTTP_UNPROCESSABLE_ENTITY,
				);
			}

			if (config('arhamlabs_pg.active_mode') === true || config('arhamlabs_pg.active_mode') === false) {
				if (config('arhamlabs_pg.active_mode') === true) {
					$response = ['statusCode' => Response::HTTP_OK, 'id' => config('arhamlabs_pg.razorpay_live_id'), 'key' => config('arhamlabs_pg.razorpay_live_secret')];

					if ($this->credentialsValidation('live')->fails()) {
						throw new Exception(
							$this->credentialsValidation('live')
								->errors()
								->first(),
								Response::HTTP_UNPROCESSABLE_ENTITY,
						);
					}
				}

				if (config('arhamlabs_pg.active_mode') === false) {
					$response = ['statusCode' => Response::HTTP_OK, 'id' => config('arhamlabs_pg.razorpay_test_id'), 'key' => config('arhamlabs_pg.razorpay_test_secret')];
					if ($this->credentialsValidation('test')->fails()) {
						throw new Exception(
							$this->credentialsValidation('test')
								->errors()
								->first(),
								Response::HTTP_UNPROCESSABLE_ENTITY,
						);
					}
				}

				if ($this->paymentCaptureValidation()->fails()) {
					throw new Exception(
						$this->paymentCaptureValidation()
							->errors()
							->first(),
							Response::HTTP_UNPROCESSABLE_ENTITY,
					);
				}

				if ($this->futureSubscriptionValidation()->fails()) {
					throw new Exception(
						$this->futureSubscriptionValidation()
							->errors()
							->first(),
							Response::HTTP_UNPROCESSABLE_ENTITY,
					);
				}

				if ($this->stopAutoRenewalValidation()->fails()) {
					throw new Exception(
						$this->stopAutoRenewalValidation()
							->errors()
							->first(),
							Response::HTTP_UNPROCESSABLE_ENTITY,
					);
				}

				if ($this->allowRefundValidation()->fails()) {
					throw new Exception(
						$this->allowRefundValidation()
							->errors()
							->first(),
							Response::HTTP_UNPROCESSABLE_ENTITY,
					);
				}
			} else {
				throw new Exception('Active mode ' . config('arhamlabs_pg.errors.boolean'), Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			Log::info('==============================================================================================================================================================================');
			Log::error("Package Validation Error");
			Log::error($e);
			Log::error("Package Validation Error");
			Log::info('==============================================================================================================================================================================');
			$response = ['statusCode' => $e->getCode(), 'message' => $e->getMessage()];
		}
		return $response;
	}
}