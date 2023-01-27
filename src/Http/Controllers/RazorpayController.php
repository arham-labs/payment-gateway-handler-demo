<?php

namespace Arhamlabs\PaymentGateway\Http\Controllers;




use Arhamlabs\PaymentGateway\Interfaces\Razorpay\SubscriptionRepositoryInterface;
use Exception;
use Carbon\Carbon;
use App\Models\PlutusPlan;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Arhamlabs\ApiResponse\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Arhamlabs\PaymentGateway\Models\PgPlan;
use Arhamlabs\PaymentGateway\Models\PgOrder;
use Arhamlabs\PaymentGateway\traits\ApiCall;


use Arhamlabs\PaymentGateway\Models\PgPayment;
use Arhamlabs\PaymentGateway\Models\PgOrderLog;
use Arhamlabs\PaymentGateway\Models\PlutusOrder;
use Arhamlabs\PaymentGateway\Models\PgPaymentLog;
use Arhamlabs\PaymentGateway\Models\PlutusPayment;
use Arhamlabs\PaymentGateway\Models\PgSubscription;
use Arhamlabs\PaymentGateway\Models\PlutusOrderLog;
use Arhamlabs\PaymentGateway\Models\RazorpayWebhook;
use Arhamlabs\PaymentGateway\Models\PlutusPaymentLog;
use Arhamlabs\PaymentGateway\Models\PlutusSubscription;
use Arhamlabs\PaymentGateway\traits\RazorpayConfigValidation;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PlanRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\OrderRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PaymentRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\OrderLogRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PaymentLogRepositoryInterface;


class RazorpayController extends Controller
{
	use RazorpayConfigValidation, ApiCall;
	public $encodeRazorKey;
	public $apiResponse;
	public $orderUrl;
	public $paymentUrl;

	public $orderRepository;
	public $orderLogRepository;

	public $paymentRepository;
	public $paymentLogRepository;

	public $planRepository;
	public $subscriptionRepository;
	public function __construct(
		ApiResponse $apiResponse,
		OrderRepositoryInterface $orderRepositoryInterface,
		OrderLogRepositoryInterface $orderLogRepositoryInterface,
		PaymentRepositoryInterface $paymentRepositoryInterface,
		PaymentLogRepositoryInterface $paymentLogRepositoryInterface,
		PlanRepositoryInterface $planRepositoryInterface,
		SubscriptionRepositoryInterface $subscriptionRepositoryInterface
	)
	{
		$this->apiResponse = $apiResponse;
		$this->orderUrl = 'https://api.razorpay.com/v1/orders/';
		$this->paymentUrl = 'https://api.razorpay.com/v1/payments/';
		if (!empty($this->validation()['id']) && !empty($this->validation()['key'])) {
			$this->encodeRazorKey = base64_encode($this->validation()['id'] . ':' . $this->validation()['key']);
		}
		$this->orderRepository = $orderRepositoryInterface;
		$this->orderLogRepository = $orderLogRepositoryInterface;
		$this->paymentRepository = $paymentRepositoryInterface;
		$this->paymentLogRepository = $paymentLogRepositoryInterface;
		$this->planRepository = $planRepositoryInterface;
		$this->subscriptionRepository = $subscriptionRepositoryInterface;
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

				if ($this->paymentCaptureValidation()->fails()) {
					throw new Exception($this->paymentCaptureValidation()->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
				}

				if ($this->futureSubscriptionValidation()->fails()) {
					throw new Exception($this->futureSubscriptionValidation()->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
				}


			} else {
				throw new Exception("Active mode " . config('arhamlabs_pg.errors.boolean'), Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			$response = ['statusCode' => $e->getCode(), 'message' => $e->getMessage()];
		}
		return $response;

	}

	public function plan($period, $interval, $amount, $notes)
	{
		try {
			if ($this->validation()['statusCode'] === 200) {
				// $period = "monthly"; // daily,weekly,monthly,year
				// $interval = 12;
				// $amount = 50;
				// $notes = [
				// 	"notes_key_1" => "Tea, Earl Grey, Hot",
				// 	"notes_key_2" => "Tea, Earl Grey… decaf."
				// ];

				$planData = [
					"period" => strtolower($period),
					"interval" => $interval,
					"item" => [
						"name" => $period . " pass",
						"amount" => $amount * 100,
						"currency" => "INR",
						"description" => "Description for the test plan - " . $period
					],
					'notes' => $notes
				];
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/plans');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($planData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$response = curl_exec($ch);
				curl_close($ch);
				$planResponse = json_decode($response);
				Log::info($response);
				if (!empty($planResponse->error)) {
					$response = $this->apiResponse->getResponse(Response::HTTP_BAD_REQUEST, array(), $planResponse->error->description);
				} else {
					// dd($planResponse->id, $period, $interval, $planResponse->item, $planResponse->notes, $planResponse->created_at);
					$this->planRepository->createPlan($planResponse->id, $period, $interval, $planResponse->item, $planResponse->notes, $planResponse->created_at);
					$response = $this->apiResponse->getResponse(200, [$planResponse], 'Plan Created');
				}


			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			Log::error($e);
			$response = ['statusCode' => $e->getCode(), 'message' => $e->getMessage()];
		}
		return $response;
	}

	public function subscription($planId, $startDate, $addons, $notes) // $planId

	{

		try {
			if ($this->validation()['statusCode'] === 200) {
				// $startDate = "2023-01-15";
				$startDate = $startDate . ' ' . date('H:i:s');
				$startDate = date("Y-m-d H:i:s", (strtotime(date($startDate)) + 5)); // add 5 sec 

				// dd();
				$currentDate = date('Y-m-d');
				// $addons = [
				// 	[
				// 		"item" => [
				// 			"name" => "Delivery charges",
				// 			"amount" => 30000,
				// 			"currency" => "INR"
				// 		]
				// 	]
				// ];
				// $notes = [
				// 	"notes_key_1" => "Tea, Earl Grey, Hot",
				// 	"notes_key_2" => "Tea, Earl Grey… decaf."
				// ];
				if ($startDate > $currentDate) {
					if (
						config('arhamlabs_pg.allow_future_subscription_payment') == false ||
						config('arhamlabs_pg.allow_future_subscription_payment') == null
					) {
						throw new Exception("In your env file, future subscriptions are set to false. Please set it to true or change the start_date.", Response::HTTP_UNPROCESSABLE_ENTITY);
					}

				}
				// $planId = "plan_L2QMFXan0kDzD6";
				$subscriptionData = [
					"plan_id" => $planId,
					"total_count" => 12,
					"quantity" => 1,
					"start_at" => strtotime($startDate),
					// "expire_by" => 1893456000,
					"customer_notify" => 1,
					"addons" => $addons,
					// "offer_id"=>"{offer_id}",
					"notes" => $notes

				];
				// dd($subscriptionData);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/subscriptions');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($subscriptionData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$jsonResponse = curl_exec($ch);
				curl_close($ch);
				Log::info($jsonResponse);

				$subscriptionResponse = json_decode($jsonResponse);
				if (!empty($subscriptionResponse->error)) {
					throw new Exception($subscriptionResponse->error->description, Response::HTTP_UNPROCESSABLE_ENTITY);
				}


				$checkSubscription = $this->subscriptionRepository->checkSubscription($subscriptionResponse->id);

				if ($checkSubscription == false) {
					$this->subscriptionRepository->createSubscription($planId, $subscriptionResponse);
				}
				Log::info($jsonResponse);
				$response = $this->apiResponse->getResponse(200, array($subscriptionResponse), 'Subscription Created');
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			// dd($e);
			Log::error($e);
			$response = $this->apiResponse->getResponse($e->getCode(), [], $e->getMessage()); // ['statusCode' => $e->getCode(), 'message' => $e->getMessage()];
		}
		return $response;
	}

	public function getSubscription($subscriptionId)
	{
		// return $subscriptionId;
		// $client = new GuzzleHttp\Client();
		// $res = $client->get('https://dashboard.razorpay.com/merchant/api/test/invoices?subscription_id=sub_L5wh3QF7WhEbgS');
		// echo $res->getStatusCode(); // 200
		// echo $res->getBody();
		$response = ApiCall::getCall('https://dashboard.razorpay.com/merchant/api/test/invoices', 'sub_L5wh3QF7WhEbgS', $this->encodeRazorKey);
		dd($response);
	}
	public function checkEmptyString($value)
	{
		return empty($value) ? null : $value;
	}

	public function checkEmptyBoolean($value)
	{
		return empty($value) ? 0 : $value;
	}

	public function checkEmptyArray($array)
	{
		return empty($array) ? [] : $array;
	}
	public function createOrder($userId, $subscriptionId, $amount, $currency = "INR", $receipt, $notes, $orderId)
	{
		// dd($orderId);
		$response = '';
		try {
			if ($this->validation()['statusCode'] === 200) {

				// $orderId = "AL" . Str::upper(Str::random(15));
				// dd();
				$createOrderData = [
					'user_id' => $this->checkEmptyString($userId),
					'rzp_subscription_id' => $this->checkEmptyString($subscriptionId),
					'rzp_order_id' => null,
					'order_id' => $orderId,
					'amount' => $amount,
					'currency' => $currency,
					'receipt' => $receipt,
					'notes' => json_decode(json_encode($notes), true, JSON_UNESCAPED_SLASHES),
					'status' => 'pending',
				];

				// Add data in our database
				$order = $this->orderRepository->createOrder($createOrderData);

				// Call Razorpay Order Api
				$orderData = [
					"amount" => $amount,
					"currency" => $currency,
					"receipt" => $receipt,
					"notes" => $notes
				];

				$response = ApiCall::postCall($this->orderUrl, $orderData, $this->encodeRazorKey);

				$orderResponse = json_decode($response);
				// dd($orderResponse);
				if (!empty($orderResponse->error)) {
					throw new Exception($orderResponse->error->description, Response::HTTP_UNPROCESSABLE_ENTITY);
				}
				if (!empty($orderResponse->status === "created")) {
					// Update order details
					$this->orderRepository->updateOrder($order->id, $orderResponse);

					// Create new order log
					$this->orderLogRepository->createOrderLog(['rzp_order_id' => $orderResponse->id, 'status' => $orderResponse->status]);
					$orderResponse->plutus_order_id = $orderId;

					$response = $this->apiResponse->getResponse(200, [$orderResponse], 'Order Created');

					// $response = $this->apiResponse->getResponse(200, [$planResponse], 'Plan Created');
				}

			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			// dd($e);
			$response = $this->apiResponse->getResponse($e->getCode(), array(), $e->getMessage());
		}
		return $response;
	}

	public function verifySignature($orderId, $paymentId, $signature)
	{
		try {
			if ($this->validation()['statusCode'] === 200) {

				$secret = config('arhamlabs_pg.razorpay_test_secret');
				$generatedSignature = hash_hmac('sha256', $orderId . "|" . $paymentId, $secret);
				if ($generatedSignature == $signature) {
					$response = $this->apiResponse->getResponse(200, array(), 'Verified Payment');
				} else {
					$response = $this->apiResponse->getResponse(422, array(), 'Unverified Payment');
				}
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			$response = $this->apiResponse->getResponse($e->getCode(), array(), $e->getMessage());
		}
		return $response;
	}

	public function payment($paymentId, $subscriptionId = null)
	{
		// dd($subscriptionId);
		try {
			if ($this->validation()['statusCode'] === 200) {

				// Get payment details by payment id
				$response = ApiCall::getCall($this->paymentUrl, $paymentId, $this->encodeRazorKey);

				$jsonData = json_decode($response);
				// dd();
				$orderId = $jsonData->order_id;
				$paymentId = $jsonData->id;
				$amount = $jsonData->amount;
				$currency = $jsonData->currency;
				$status = $jsonData->status;
				$invoiceId = $this->checkEmptyString($jsonData->invoice_id);
				$international = $this->checkEmptyString($jsonData->international);
				$method = $this->checkEmptyString($jsonData->method);
				$amountRefunded = $this->checkEmptyBoolean($jsonData->amount_refunded);
				$refundStatus = $this->checkEmptyString($jsonData->refund_status);
				$captured = $this->checkEmptyBoolean($jsonData->captured);
				$description = $this->checkEmptyString($jsonData->description);
				$cardId = $this->checkEmptyString($jsonData->card_id);
				$bank = $this->checkEmptyString($jsonData->bank);
				$wallet = $this->checkEmptyString($jsonData->wallet);
				$vpa = $this->checkEmptyString($jsonData->vpa);
				$email = $this->checkEmptyString($jsonData->email);
				$contact = $this->checkEmptyString($jsonData->contact);
				$tokenId = empty($jsonData->token_id) ? null : $jsonData->token_id;
				$notes = json_decode(json_encode($jsonData->notes), true, JSON_UNESCAPED_SLASHES); //$this->checkEmptyArray($jsonData->notes);
				$fee = $this->checkEmptyString($jsonData->fee);
				$tax = $this->checkEmptyString($jsonData->tax);
				$errorCode = $this->checkEmptyString($jsonData->error_code);
				$errorDescription = $this->checkEmptyString($jsonData->error_description);
				$errorSource = $this->checkEmptyString($jsonData->error_source);
				$errorStep = $this->checkEmptyString($jsonData->error_step);
				$errorReason = $this->checkEmptyString($jsonData->error_reason);
				$acquirerData = json_decode(json_encode($jsonData->acquirer_data), true, JSON_UNESCAPED_SLASHES); //$this->checkEmptyArray($jsonData->acquirer_data);
				$createdAtTimestamp = $this->checkEmptyString($jsonData->created_at);


				$order = $this->orderRepository->checkOrderById($orderId);


				if (!empty($order)) {
					// DB::enableQueryLog();
					$checkPayment = PlutusPayment::where('rzp_order_id', $orderId)->exists();
					// dd($checkPayment, DB::getQueryLog());					
					if ($checkPayment == false) {
						$paymentResult = [
							'order_id' => $orderId,
							'payment_id' => $paymentId,
							'amount' => $amount,
							'currency' => $currency,
							'status' => $status,
							'invoice_id' => $invoiceId,
							'international' => $international,
							'method' => $method,
							'amountRefunded' => $amountRefunded,
							'amount_refunded' => $amountRefunded,
							'refundStatus' => $refundStatus,
							'refund_status' => $refundStatus,
							'captured' => $captured,
							'description' => $description,
							// 'cardId' => $cardId,
							'card_id' => $cardId,
							'bank' => $bank,
							'wallet' => $wallet,
							'vpa' => $vpa,
							'email' => $email,
							'contact' => $contact,
							'token_id' => $tokenId,
							'notes' => json_decode(json_encode($notes), true, JSON_UNESCAPED_SLASHES), //json_encode($notes),
							'fee' => $fee,
							'tax' => $tax,
							// 'errorCode' => $errorCode,
							'error_code' => $errorCode,
							// 'errorDescription' => $errorDescription,
							'error_description' => $errorDescription,
							// 'errorSource' => $errorSource,
							'error_source' => $errorSource,
							// 'errorStep' => $errorStep,
							'error_step' => $errorStep,
							// 'errorReason' => $errorReason,
							'error_reason' => $errorReason,
							// 'acquirerData' => json_encode($acquirerData),
							'acquirer_data' => json_decode(json_encode($acquirerData), true, JSON_UNESCAPED_SLASHES), //json_encode($acquirerData),
							'created_at' => (string) $createdAtTimestamp,
							// 'createdAtTimestamp' => (string) $createdAtTimestamp,
						];
						$this->paymentRepository->createPayment((object) $paymentResult);
						$this->updatePaymentStatus($paymentId);
						if (!empty(config('arhamlabs_pg.allow_capture_payment')) && config('arhamlabs_pg.allow_capture_payment') == true) {
							$this->capturePayment($paymentId, $amount, $orderId);
						}
					}
					// else {
					// 	throw new Exception("Payment already exists for the specified payment id $paymentId", Response::HTTP_UNPROCESSABLE_ENTITY);
					// }
					$this->updateOrderStatus($orderId);
					$this->updatePaymentStatus($paymentId);
					$response = $this->apiResponse->getResponse(200, array('order_id' => $orderId, 'payment' => $jsonData)); // , '','','', [$capturePayment]
				} else {
					$this->updateOrderStatus($orderId, $subscriptionId);
					$this->updatePaymentStatus($paymentId);
					$response = $this->apiResponse->getResponse(200, array('order_id' => $orderId, 'payment' => $jsonData)); // , '','','', [$capturePayment]

					// throw new Exception("Order could not be found for the specified payment id $paymentId", Response::HTTP_UNPROCESSABLE_ENTITY);
				}
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			// dd($e);
			$response = $this->apiResponse->getResponse($e->getCode(), array(), $e->getMessage());
		}
		return $response;
	}

	public function capturePayment($paymentId, $amount, $orderId)
	{
		try {
			if ($this->validation()['statusCode'] === 200) {

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/payments/$paymentId/capture");
				$postData = [
					"amount" => $amount
				];
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$response = curl_exec($ch);
				curl_close($ch);
				Log::info($response);
				$jsonResponse = json_decode($response);
				Log::info((array) $jsonResponse);
				if (!empty($jsonResponse->error)) {
					$response = $this->apiResponse->getResponse(400, ['payment_id' => $paymentId, 'order_id' => $orderId], $jsonResponse->error->description);
				}
				if (!empty($jsonResponse->status)) {
					PlutusPayment::where('payment_id', $paymentId)->update(['status' => $jsonResponse->status]);
					$this->updateOrderStatus($orderId);
					$this->updatePaymentStatus($paymentId);
					$response = $this->apiResponse->getResponse(200, $jsonResponse, $jsonResponse->status);
				}
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			$response = $this->apiResponse->getResponse($e->getCode(), array(), $e->getMessage());
		}
		return $response;

	}

	public function updateOrderStatus($orderId, $subscriptionId = null)
	{
		$checkOrder = PlutusOrder::where('rzp_order_id', $orderId)->exists();
		if ($checkOrder == true) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/orders/$orderId");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
			$response = curl_exec($ch);
			curl_close($ch);
			Log::info($response);
			$jsonData = json_decode($response);
			$status = $jsonData->status;

			$checkOrderLogs = PlutusOrderLog::where(['rzp_order_id' => $orderId, 'status' => $status])->exists();
			if ($checkOrderLogs == false) {
				$plutusOrderLog = new PlutusOrderLog;
				$plutusOrderLog->uuid = Str::uuid();
				$plutusOrderLog->rzp_order_id = $orderId;
				$plutusOrderLog->status = $status;
				$plutusOrderLog->save();
			}
			PlutusOrder::where('rzp_order_id', $orderId)->update(['status' => $status]);
		} else {
			$order = $this->orderRepository->getOrderByOrderId($this->orderUrl, $orderId, $this->encodeRazorKey);
			$orderResult = json_decode($order);


			$createOrderData = [
				'user_id' => null,
				'rzp_subscription_id' => $this->checkEmptyString($subscriptionId),
				'rzp_order_id' => $orderResult->id,
				'amount' => $orderResult->amount,
				'currency' => $orderResult->currency,
				'receipt' => $orderResult->receipt,
				'notes' => json_encode($orderResult->notes),
				'status' => $orderResult->status,
			];

			// Add data in our database
			$order = $this->orderRepository->createOrder($createOrderData);
		}
	}
	public function updatePaymentStatus($paymentId)
	{
		$checkPayment = PlutusPayment::where('rzp_payment_id', $paymentId)->exists();
		if ($checkPayment == true) {

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/payments/$paymentId");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
			$response = curl_exec($ch);
			curl_close($ch);
			$jsonData = json_decode($response);
			$status = $jsonData->status;
			$checkPaymentLogs = PlutusPaymentLog::where(['rzp_payment_id' => $paymentId, 'status' => $status])->exists();
			if ($checkPaymentLogs == false) {
				$plutusPaymentLog = new PlutusPaymentLog;
				$plutusPaymentLog->uuid = Str::uuid();
				$plutusPaymentLog->rzp_payment_id = $paymentId;
				$plutusPaymentLog->status = $status;
				$plutusPaymentLog->save();
			}
			PlutusPayment::where('rzp_payment_id', $paymentId)->update(['status' => $status]);
		} else {
			$payment = $this->paymentRepository->getPaymentByPaymentId($this->paymentUrl, $paymentId, $this->encodeRazorKey);
			$paymentResult = json_decode($payment);
			// $paymentResult->orderId = $paymentResult->order_id;
			// $paymentResult->paymentId = $paymentResult->id;
			$status = $paymentResult->status;
			dd($paymentResult, $status);
			$checkPaymentLogs = PlutusPaymentLog::where(['rzp_payment_id' => $paymentId, 'status' => $status])->exists();
			if ($checkPaymentLogs == false) {
				$plutusPaymentLog = new PlutusPaymentLog;
				$plutusPaymentLog->uuid = Str::uuid();
				$plutusPaymentLog->rzp_payment_id = $paymentId;
				$plutusPaymentLog->status = $status;
				$plutusPaymentLog->save();
			}
			$this->paymentRepository->createPayment($paymentResult);


		}
	}

	public function webhook()
	{
		$data = file_get_contents('php://input');
		if (!empty($data)) {
			$jsonData = json_decode($data);
			if ($jsonData->event == 'subscription.charged') {
				$this->subscriptionCharged($data);
			}
			// if ($jsonData->event == 'subscription.cancelled') {
			// 	$this->cancelled_subscription($data, $booking_id);
			// }
			if ($jsonData->event == 'payment.captured') {
				$this->paymentCaptured($data);
			}
			if ($jsonData->event == 'payment.authorized') {
				$this->paymentAuthorized($data);
			}
			if ($jsonData->event == 'payment.failed') {
				$this->paymentFailed($data);
			}
		} else {
			// log_message('error', 'data not set');
		}
		// dd($data);
		return $this->apiResponse->getResponse(200, [], "Webhook Start");
	}

	public function paymentAuthorized($data)
	{
		Log::info("Payment Authorized Start");
		Log::info($data);
		$jsonData = json_decode($data);
		$jsonData = $jsonData->payload->payment->entity;
		// dd($jsonData->order_id);
		$orderId = $jsonData->order_id;
		$paymentId = $jsonData->id;
		$amount = $jsonData->amount;
		$currency = $jsonData->currency;
		$status = $jsonData->status;
		$invoiceId = empty($jsonData->invoice_id) ? null : $jsonData->invoice_id;
		$international = empty($jsonData->international) ? null : $jsonData->international;
		$method = empty($jsonData->method) ? null : $jsonData->method;
		$amountRefunded = empty($jsonData->amount_refunded) ? null : $jsonData->amount_refunded;
		$refundStatus = empty($jsonData->refund_status) ? null : $jsonData->refund_status;
		$captured = empty($jsonData->captured) ? null : $jsonData->captured;
		$description = empty($jsonData->description) ? null : $jsonData->description;
		$cardId = empty($jsonData->card_id) ? null : $jsonData->card_id;
		$bank = empty($jsonData->bank) ? null : $jsonData->bank;
		$wallet = empty($jsonData->wallet) ? null : $jsonData->wallet;
		$vpa = empty($jsonData->vpa) ? null : $jsonData->vpa;
		$email = empty($jsonData->email) ? null : $jsonData->email;
		$contact = empty($jsonData->contact) ? null : $jsonData->contact;
		$tokenId = empty($jsonData->token_id) ? null : $jsonData->token_id;
		$notes = empty($jsonData->notes) ? [] : $jsonData->notes;
		$fee = empty($jsonData->fee) ? null : $jsonData->fee;
		$tax = empty($jsonData->tax) ? null : $jsonData->tax;
		$errorCode = empty($jsonData->error_code) ? null : $jsonData->error_code;
		$errorDescription = empty($jsonData->error_description) ? null : $jsonData->error_description;
		$errorSource = empty($jsonData->error_source) ? null : $jsonData->error_source;
		$errorStep = empty($jsonData->error_step) ? null : $jsonData->error_step;
		$errorReason = empty($jsonData->error_reason) ? null : $jsonData->error_reason;
		$acquirerData = empty($jsonData->acquirer_data) ? [] : null;
		$createdAtTimestamp = empty($jsonData->created_at) ? null : $jsonData->created_at;

		$razorpayWebhook = new RazorpayWebhook;
		$razorpayWebhook->uuid = Str::uuid();
		$razorpayWebhook->event = "payment.authorized";
		$razorpayWebhook->order_id = $orderId;
		$razorpayWebhook->payment_id = $paymentId;
		$razorpayWebhook->payload = $data;
		$razorpayWebhook->rzp_created_at = $createdAtTimestamp;

		$razorpayWebhook->save();

		Log::info($status);
		DB::enableQueryLog();
		$checkPayment = PgPayment::where('payment_id', $paymentId)->exists();
		if ($checkPayment == true) {
			Log::info("$paymentId exists");
			PgPayment::where('payment_id', $paymentId)->update(['status' => $status]);
		} else {
			Log::info("$paymentId not exists");
			$order = PgOrder::select('id')->where('order_id', $orderId)->first();
			if (!empty($order)) {
				PgPayment::insertOrIgnore([
					'uuid' => Str::uuid(),
					'order_id' => $order->id,
					'payment_id' => $paymentId,
					'amount' => $amount,
					'currency' => $currency,
					'status' => $status,
					'invoice_id' => $invoiceId,
					'international' => $international,
					'method' => $method,
					'amount_refunded' => $amountRefunded,
					'refund_status' => $refundStatus,
					'captured' => $captured,
					'description' => $description,
					'card_id' => $cardId,
					'bank' => $bank,
					'wallet' => $wallet,
					'vpa' => $vpa,
					'email' => $email,
					'contact' => $contact,
					'token_id' => $tokenId,
					'notes' => json_encode($notes),
					'fee' => $fee,
					'tax' => $tax,
					'error_code' => $errorCode,
					'error_description' => $errorDescription,
					'error_source' => $errorSource,
					'error_step' => $errorStep,
					'error_reason' => $errorReason,
					'acquirer_data' => json_encode($acquirerData),
					'created_at_timestamp' => (string) $createdAtTimestamp,
				]);
			}




		}
		$checkOrderLogs = PgPaymentLog::where(['payment_id' => $paymentId, 'status' => $status])->exists();
		if ($checkOrderLogs == false) {
			$pgPaymentLog = new PgPaymentLog;
			$pgPaymentLog->uuid = Str::uuid();
			$pgPaymentLog->payment_id = $paymentId;
			$pgPaymentLog->status = $status;
			$pgPaymentLog->save();
		}
		Log::info(DB::getQueryLog());
		Log::info("Payment Authorized End");
	}

	public function paymentCaptured($data)
	{
		Log::info("Payment Captured Start");
		Log::info($data);
		$jsonData = json_decode($data);
		$jsonData = $jsonData->payload->payment->entity;
		$paymentId = $jsonData->id;
		$orderId = $jsonData->order_id;
		$status = $jsonData->status;
		$createdAtTimestamp = $jsonData->created_at;
		$razorpayWebhook = new RazorpayWebhook;
		$razorpayWebhook->uuid = Str::uuid();
		$razorpayWebhook->event = "payment.captured";
		$razorpayWebhook->order_id = $orderId;
		$razorpayWebhook->payment_id = $paymentId;
		$razorpayWebhook->payload = $data;
		$razorpayWebhook->rzp_created_at = $createdAtTimestamp;
		$razorpayWebhook->save();
		Log::info($status);
		DB::enableQueryLog();
		$checkPayment = PgPayment::where('payment_id', $paymentId)->exists();
		if ($checkPayment == true) {
			Log::info("$paymentId exists");
			PgPayment::where('payment_id', $paymentId)->update(['status' => $status]);
		}
		$checkOrderLogs = PgPaymentLog::where(['payment_id' => $paymentId, 'status' => $status])->exists();
		if ($checkOrderLogs == false) {
			$pgPaymentLog = new PgPaymentLog;
			$pgPaymentLog->uuid = Str::uuid();
			$pgPaymentLog->payment_id = $paymentId;
			$pgPaymentLog->status = $status;
			$pgPaymentLog->save();
		}
		Log::info(DB::getQueryLog());
		Log::info("Payment Captured End");
	}

	public function paymentFailed($data)
	{
		Log::info("Payment Failed Start");
		Log::info($data);
		$jsonData = json_decode($data);
		$jsonData = $jsonData->payload->payment->entity;
		$paymentId = $jsonData->id;
		$orderId = $jsonData->order_id;
		$status = $jsonData->status;
		$createdAtTimestamp = $jsonData->created_at;
		$razorpayWebhook = new RazorpayWebhook;
		$razorpayWebhook->uuid = Str::uuid();
		$razorpayWebhook->event = "payment.failed";
		$razorpayWebhook->order_id = $orderId;
		$razorpayWebhook->payment_id = $paymentId;
		$razorpayWebhook->payload = $data;
		$razorpayWebhook->rzp_created_at = $createdAtTimestamp;
		$razorpayWebhook->save();
		Log::info($status);
		DB::enableQueryLog();
		$checkPayment = PgPayment::where('payment_id', $paymentId)->exists();
		if ($checkPayment == true) {
			Log::info("$paymentId exists");
			PgPayment::where('payment_id', $paymentId)->update(['status' => $status]);
		}
		$checkOrderLogs = PgPaymentLog::where(['payment_id' => $paymentId, 'status' => $status])->exists();
		if ($checkOrderLogs == false) {
			$pgPaymentLog = new PgPaymentLog;
			$pgPaymentLog->uuid = Str::uuid();
			$pgPaymentLog->payment_id = $paymentId;
			$pgPaymentLog->status = $status;
			$pgPaymentLog->save();
		}
		Log::info(DB::getQueryLog());
		Log::info("Payment Failed End");
	}

	public function subscriptionCharged($data)
	{
		Log::info("Subscription Charged Start");

		// $data = '{
		// 	"entity": "event",
		// 	"account_id": "acc_BFQ7uQEaa7j2z7",
		// 	"event": "subscription.charged",
		// 	"contains": [
		// 	  "subscription",
		// 	  "payment"
		// 	],
		// 	"payload": {
		// 	  "subscription": {
		// 		"entity": {
		// 		  "id": "sub_L2MBXeUDYxOSfx",
		// 		  "entity": "subscription",
		// 		  "plan_id": "plan_BvrFKjSxauOH7N",
		// 		  "customer_id": "cust_C0WlbKhp3aLA7W",
		// 		  "status": "charged",
		// 		  "type": 2,
		// 		  "current_start": 1570213800,
		// 		  "current_end": 1572892200,
		// 		  "ended_at": null,
		// 		  "quantity": 1,
		// 		  "notes": {
		// 			"Important": "Notes for Internal Reference"
		// 		  },
		// 		  "charge_at": 1572892200,
		// 		  "start_at": 1570213800,
		// 		  "end_at": 1599244200,
		// 		  "auth_attempts": 0,
		// 		  "total_count": 12,
		// 		  "paid_count": 1,
		// 		  "customer_notify": true,
		// 		  "created_at": 1567689895,
		// 		  "expire_by": 1567881000,
		// 		  "short_url": null,
		// 		  "has_scheduled_changes": false,
		// 		  "change_scheduled_at": null,
		// 		  "source": "api",
		// 		  "offer_id":"offer_JHD834hjbxzhd38d",
		// 		  "remaining_count": 11
		// 		}
		// 	  },
		// 	  "payment": {
		// 		"entity": {
		// 		  "id": "pay_DEXFWroJ6LikKT",
		// 		  "entity": "payment",
		// 		  "amount": 100000,
		// 		  "currency": "INR",
		// 		  "status": "captured",
		// 		  "order_id": "order_DEXFWXwO24pDxH",
		// 		  "invoice_id": "inv_DEXFWVuM6rPqlK",
		// 		  "international": false,
		// 		  "method": "card",
		// 		  "amount_refunded": 0,
		// 		  "amount_transferred": 0,
		// 		  "refund_status": null,
		// 		  "captured": "1",
		// 		  "description": "Recurring Payment via Subscription",
		// 		  "card_id": "card_DEXFX0KGtXexrH",
		// 		  "card": {
		// 			"id": "card_DEXFX0KGtXexrH",
		// 			"entity": "card",
		// 			"name": "Gaurav Kumar",
		// 			"last4": "5558",
		// 			"network": "MasterCard",
		// 			"type": "credit",
		// 			"issuer": "KARB",
		// 			"international": false,
		// 			"emi": false,
		// 			"expiry_month": 2,
		// 			"expiry_year": 2022
		// 		  },
		// 		  "bank": null,
		// 		  "wallet": null,
		// 		  "vpa": null,
		// 		  "email": "gaurav.kumar@example.com",
		// 		  "contact": "+919876543210",
		// 		  "customer_id": "cust_C0WlbKhp3aLA7W",
		// 		  "token_id": null,
		// 		  "notes": [],
		// 		  "fee": 2900,
		// 		  "tax": 0,
		// 		  "error_code": null,
		// 		  "error_description": null,
		// 		  "created_at": 1567690382
		// 		}
		// 	  }
		// 	},
		// 	"created_at": 1567690383
		//   }';
		Log::info($data);
		exit;
		$jsonData = json_decode($data);
		$subscriptionJsonData = $jsonData->payload->subscription->entity;
		$paymentJsonData = $jsonData->payload->payment->entity;
		$subscriptionId = $subscriptionJsonData->id;
		$subscriptionStatus = $subscriptionJsonData->status;
		$paymentId = $paymentJsonData->id;
		$paymentStatus = $paymentJsonData->status;
		$orderId = $paymentJsonData->order_id;
		$amount = $paymentJsonData->amount;
		$notes = $paymentJsonData->notes;
		$receipt = empty($paymentJsonData->receipt) ? null : $paymentJsonData->receipt;
		$createdAtTimestamp = $jsonData->created_at;
		$razorpayWebhook = new RazorpayWebhook;
		$razorpayWebhook->uuid = Str::uuid();
		$razorpayWebhook->event = "subscription.charged";
		$razorpayWebhook->order_id = $orderId;
		$razorpayWebhook->payment_id = $paymentId;
		$razorpayWebhook->subscription_Id = $subscriptionId;
		$razorpayWebhook->payload = $data;
		$razorpayWebhook->rzp_created_at = $createdAtTimestamp;
		$razorpayWebhook->save();
		DB::enableQueryLog();
		$checkSubscription = PgSubscription::where('subscription_id', $subscriptionId)->exists();
		if (!empty($checkSubscription) || $checkSubscription == true) {
			PgSubscription::where('subscription_id', $subscriptionId)
				->update(['status' => $subscriptionStatus]);

			// Order 
			$this->createOrder(null, $subscriptionId, $amount, $currency = "INR", $receipt, $notes);
			// Payment
			// $this->payment($paymentId);
			// $this->updateOrderStatus($orderId);
			// $this->updatePaymentStatus($paymentId);

		}
		Log::info("subscription status " . $subscriptionStatus);
		Log::info("subscription id " . $subscriptionId);
		Log::info("check subscription " . $checkSubscription);
		Log::info(DB::getQueryLog());
		Log::info("Subscription Charged End");
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