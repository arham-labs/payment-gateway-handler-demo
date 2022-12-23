<?php

namespace Arhamlabs\PaymentGateway\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


use Arhamlabs\ApiResponse\ApiResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Arhamlabs\PaymentGateway\traits\RazorpayConfigValidation;


class RazorpayController extends Controller
{
	use RazorpayConfigValidation;
	public $apiResponse;
	public $orderUrl;
	public function __construct(ApiResponse $apiResponse)
	{
		$this->apiResponse = $apiResponse;
		$this->orderUrl = 'https://api.razorpay.com/v1/orders';
	}
	public function validation()
	{
		$response = '';
		try {
			if ($this->activeModeValidation()->fails()) {
				throw new Exception($this->activeModeValidation()->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
			}

			if (config('arhamlabs_pg.active_mode') === true || config('arhamlabs_pg.active_mode') === false) {
				if (config('arhamlabs_pg.active_mode') === true) {
					$response = ['statusCode' => Response::HTTP_OK, 'id' => config('arhamlabs_pg.razorpay_live_id'), 'key' => config('arhamlabs_pg.razorpay_live_secret')];

					if ($this->credentialsValidation('live')->fails()) {
						throw new Exception($this->credentialsValidation('live')->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
					}
				}
				if (config('arhamlabs_pg.active_mode') === false) {
					$response = ['statusCode' => Response::HTTP_OK, 'id' => config('arhamlabs_pg.razorpay_test_id'), 'key' => config('arhamlabs_pg.razorpay_test_secret')];
					if ($this->credentialsValidation('test')->fails()) {
						throw new Exception($this->credentialsValidation('test')->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
					}
				}


			} else {
				throw new Exception("Active mode " . config('arhamlabs_pg.errors.boolean'), Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			$response = ['statusCode' => $e->getCode(), 'message' => $e->getMessage()];
		}
		return $response;

	}

	public function createOrder($amount, $currency = "INR", $receipt, $notes)
	{
		$orderData = [
			"amount" => $amount,
			"currency" => $currency,
			"receipt" => $receipt,
			"notes" => $notes
		];

		$encodeRazorKey = base64_encode($this->validation()['id'] . ':' . $this->validation()['key']);

		$response = '';
		try {
			if ($this->validation()['statusCode'] === 200) {
				// Call Razorpay Order Api
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->orderUrl);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($orderData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $encodeRazorKey]);
				$response = curl_exec($ch);
				curl_close($ch);
				$orderResponse = json_decode($response);

				if (!empty($orderResponse->error)) {
					$response = $this->apiResponse->getResponse(Response::HTTP_BAD_REQUEST, array(), $orderResponse->error->description);
				}
				if (!empty($orderResponse->status === "created")) {
					return $this->apiResponse->getResponse(200, array('order_id' => $orderResponse->id), 'Order Created');
				}

			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			$response = $this->apiResponse->getResponse($e->getCode(), array(), $e->getMessage());
		}
		return $response;
	}

	public function template()
	{
		$response = '';
		try {
			if ($this->validation()['statusCode'] === 200) {
				$response = $this->validation();
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			$response = $this->apiResponse->getResponse($e->getCode(), array(), $e->getMessage());
		}
		return $response;
	}
}