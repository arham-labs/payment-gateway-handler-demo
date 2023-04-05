<?php

namespace Arhamlabs\PaymentGateway\Http\Controllers;

use Arhamlabs\PaymentGateway\Models\PlutusTransfer;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Arhamlabs\ApiResponse\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Arhamlabs\PaymentGateway\traits\ApiCall;


use Arhamlabs\PaymentGateway\Models\PlutusPlan;
use Arhamlabs\PaymentGateway\Models\PlutusOrder;
use Arhamlabs\PaymentGateway\Models\PlutusRefund;
use Arhamlabs\PaymentGateway\Models\PlutusPayment;
use Arhamlabs\PaymentGateway\Models\PlutusOrderLog;
use Arhamlabs\PaymentGateway\Models\RazorpayWebhook;
use Arhamlabs\PaymentGateway\Models\PlutusPaymentLog;
use Arhamlabs\PaymentGateway\Models\PlutusSubscription;
use Arhamlabs\PaymentGateway\Models\PlutusSubscriptionLog;
use Arhamlabs\PaymentGateway\traits\RazorpayConfigValidation;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PlanRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\OrderRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PaymentRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\OrderLogRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\PaymentLogRepositoryInterface;
use Arhamlabs\PaymentGateway\Interfaces\Razorpay\SubscriptionRepositoryInterface;

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

	public $client;


	public function __construct(ApiResponse $apiResponse, OrderRepositoryInterface $orderRepositoryInterface, OrderLogRepositoryInterface $orderLogRepositoryInterface, PaymentRepositoryInterface $paymentRepositoryInterface, PaymentLogRepositoryInterface $paymentLogRepositoryInterface, PlanRepositoryInterface $planRepositoryInterface, SubscriptionRepositoryInterface $subscriptionRepositoryInterface)
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
		// $this->client = new \GuzzleHttp\Client([
		// 	'headers' => $this->headers
		// ]);
	}


	public function checkPlan($planId)
	{
		// try {
		// 	if ($this->planRepository->checkPlan($planId) == true) {
		// 		$response = (object) ['statusCode' => 200, 'message' => 'Plan Exists', 'data' => $this->getPlan($planId)->data];
		// 	} else {
		// 		$response = (object) ['statusCode' => 404, 'message' => 'Plan Not Found', 'data' => [$this->planRepository->checkPlan($planId)]];
		// 	}

		// } catch (Exception $e) {
		// 	$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => $e];
		// }
		return $this->planRepository->checkPlan($planId);

	}

	public function createPrePlan($planId)
	{
		try {
			$planResponseData = $this->getPlan($planId);
			if ($planResponseData->statusCode == 200) {
				if ($planResponseData->data) {
					$planResponse = json_decode($planResponseData->data);
					$planData = $this->planRepository->createPlan($planResponse->id, $planResponse->period, $planResponse->interval, $planResponse->item, $planResponse->notes, $planResponse->created_at);
					$response = (object) ['statusCode' => 200, 'message' => 'Plan Created', 'data' => $planData, 'error' => []];
				}
			}
		} catch (Exception $e) {
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage()];
		}
		return $response;
	}
	public function plan($period, $interval, $amount, $notes)
	{
		try {
			if ($this->validation()['statusCode'] === 200) {


				$planData = [
					'period' => strtolower($period),
					// $periodName
					'interval' => $interval,
					'item' => [
						'name' => $period,
						'amount' => $amount * 100,
						'currency' => 'INR',
						'description' => 'Description',
					],
					'notes' => $notes,
				];
				// dd($planData, $notes);
				Log::info('==============================================================================================================================================================================');
				Log::error("Package Plan Param");
				Log::error((array) $planData);
				Log::error("Package Plan Param");
				Log::info('==============================================================================================================================================================================');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/plans');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($planData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$response = curl_exec($ch);
				Log::info('==============================================================================================================================================================================');
				Log::error("Package Plan Data");
				Log::error((array) $response);
				Log::error("Package Plan Data");
				Log::info('==============================================================================================================================================================================');
				curl_close($ch);
				$planResponse = json_decode($response);

				Log::info($response);
				if (!empty($planResponse->error)) {
					// $response = $this->apiResponse->getResponse(Response::HTTP_BAD_REQUEST, array(), $planResponse->error->description);
					$response = (object) ['statusCode' => Response::HTTP_BAD_REQUEST, 'message' => $planResponse->error->description, 'data' => [], 'error' => $planResponse->error];
				} else {
					// dd($planResponse->id, $period, $interval, $planResponse->item, $planResponse->notes, $planResponse->created_at);
					$this->planRepository->createPlan($planResponse->id, $period, $interval, $planResponse->item, $planResponse->notes, $planResponse->created_at);
					// $response = $this->apiResponse->getResponse(200, [$planResponse], 'Plan Created');
					$response = (object) ['statusCode' => 200, 'message' => 'Plan Created', 'data' => $planResponse, 'error' => []];
				}
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			Log::info('==============================================================================================================================================================================');
			Log::error("Package Plan Error");
			Log::error($e);
			Log::error("Package Plan Error");
			Log::info('==============================================================================================================================================================================');
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage()];
		}
		return $response;
	}

	public function getPlan($planId)
	{
		try {
			$url = "https://api.razorpay.com/v1/plans/";
			$apiResponse = $this->getCall($url, $planId, $this->encodeRazorKey);

			$response = (object) [
				'statusCode' => 200,
				'message' => "Plan Data",
				'data' => $apiResponse,
				'error' => []
			];
		} catch (Exception $e) {
			$response = (object) [
				'statusCode' => $e->getCode(),
				'message' => $e->getMessage(),
				'data' => [],
				'error' => []
			];
		}
		return $response;
	}

	public function subscription($planId, $date, $addons, $notes, $offerId)
	{
		Log::info('==============================================================================================================================================================================');
		Log::info('Package Subscription Start');

		try {
			if ($this->validation()['statusCode'] === 200) {
				// $startDate = "2023-01-15";
				$startDate = null;
				if (!empty($date)) {
					$startDate = $date . ' ' . date('H:i:s');
					$startDate = date('Y-m-d H:i:s', strtotime(date($startDate)) + 5); // add 5 sec
				}

				$currentDate = date('Y-m-d');
				if ($startDate > $currentDate) {
					if (config('arhamlabs_pg.allow_future_subscription_payment') == false || config('arhamlabs_pg.allow_future_subscription_payment') == null) {
						throw new Exception('In your env file, future subscriptions are set to false. Please set it to true or change the start_date.', Response::HTTP_UNPROCESSABLE_ENTITY);
					}
				}

				if (!empty($notes) && !empty($notes['stop_auto_renewal'])) {
					if ($notes['stop_auto_renewal'] == 1 && config('arhamlabs_pg.stop_auto_renewal') == false) {
						throw new Exception('In your env file, auto renewal are set to false. Please set it to true or send 0 as stop_auto_renewal', Response::HTTP_UNPROCESSABLE_ENTITY);
					}
				}

				$subscriptionData = [
					'plan_id' => $planId,
					'total_count' => 12,
					'quantity' => 1,
					'start_at' => !empty($startDate) ? strtotime($startDate) : $startDate,
					// "expire_by" => 1893456000,
					'customer_notify' => 0,
					'addons' => $addons,
					'offer_id' => $offerId,
					'notes' => $notes,
				];

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/subscriptions');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($subscriptionData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$jsonResponse = curl_exec($ch);
				curl_close($ch);
				Log::info('==============================================================================================================================================================================');
				Log::info((array) $jsonResponse);
				Log::info('==============================================================================================================================================================================');
				$subscriptionResponse = json_decode($jsonResponse);
				if (!empty($subscriptionResponse->error)) {
					throw new Exception($subscriptionResponse->error->description, Response::HTTP_UNPROCESSABLE_ENTITY);
				}

				if (!empty($subscriptionResponse->id)) {
					$checkSubscription = $this->subscriptionRepository->checkSubscription($subscriptionResponse->id);

					if ($checkSubscription == false) {
						$this->subscriptionRepository->createSubscription($planId, $subscriptionResponse);
					}

					$checkSubscriptionLog = PlutusSubscriptionLog::where([
						'rzp_subscription_id' => $subscriptionResponse->id,
						'status' => $subscriptionResponse->status,
					])->exists();
					$checkSubscriptionLog = PlutusSubscriptionLog::where(['rzp_subscription_id' => $subscriptionResponse->id, 'status' => $subscriptionResponse->status])->exists();
					if ($checkSubscriptionLog == false) {
						$checkSubscriptionLog = PlutusSubscriptionLog::create([
							'uuid' => Str::uuid(),
							'rzp_plan_id' => $subscriptionResponse->plan_id,
							'rzp_subscription_id' => $subscriptionResponse->id,
							'rzp_customer_id' => empty($subscriptionResponse->customer_id) ? null : $subscriptionResponse->customer_id,
							'status' => $subscriptionResponse->status,
							'quantity' => $subscriptionResponse->quantity,
							'notes' => json_encode($subscriptionResponse->notes),
							'charge_at_timestamp' => $subscriptionResponse->charge_at,
							'start_at_timestamp' => $subscriptionResponse->start_at,
							'end_at_timestamp' => $subscriptionResponse->end_at,
							'total_count' => $subscriptionResponse->total_count,
							'paid_count' => $subscriptionResponse->paid_count,
							'customer_notify' => $subscriptionResponse->customer_notify,
							'addons' => empty($subscriptionResponse->addons) ? null : json_encode($subscriptionResponse->addons),
							'created_at_timestamp' => $subscriptionResponse->created_at,
							'expire_by_timestamp' => $subscriptionResponse->expire_by,
							'has_scheduled_changes' => $subscriptionResponse->has_scheduled_changes,
							'remaining_count' => $subscriptionResponse->remaining_count,
							'source' => $subscriptionResponse->source,
						]);
					}

					// $response = $this->apiResponse->getResponse(200, array($subscriptionResponse), 'Subscription Created');
					$response = (object) ['statusCode' => 200, 'message' => 'Subscription Created', 'data' => $subscriptionResponse, 'error' => []];
				} else {
					throw new Exception('Empty Response From Razorpay', Response::HTTP_UNPROCESSABLE_ENTITY);
					// If curl not execute
				}
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			// dd($e);
			Log::info('==============================================================================================================================================================================');
			Log::error('Package Subscription Error');
			Log::error($e);
			Log::error('Package Subscription Error');
			Log::info('==============================================================================================================================================================================');
			// $response = $this->apiResponse->getResponse($e->getCode(), [], $e->getMessage()); // ['statusCode' => $e->getCode(), 'message' => $e->getMessage()];
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => $e->getMessage()];
		}
		Log::info('Package Subscription End');
		Log::info('==============================================================================================================================================================================');
		return $response;
	}

	public function updateSubscriptionStatus($subscriptionId)
	{
		try {
			$subscription = PlutusSubscription::where(['rzp_subscription_id' => $subscriptionId])->first();
			if (!empty($subscription)) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/subscriptions/$subscriptionId");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				// curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($subscriptionData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$jsonResponse = curl_exec($ch);
				Log::info('==============================================================================================================================================================================');
				Log::info("Package Update Subscription Status");
				Log::info((array) $jsonResponse);
				Log::info("Package Update Subscription Status");
				Log::info('==============================================================================================================================================================================');
				curl_close($ch);
				$subscriptionResponse = json_decode($jsonResponse);
				$updateData = [
					'status' => $subscriptionResponse->status,
					'current_start_timestamp' => $subscriptionResponse->current_start,
					'current_end_timestamp' => $subscriptionResponse->current_end,
					'ended_at_timestamp' => $subscriptionResponse->ended_at,
					'charge_at_timestamp' => $subscriptionResponse->charge_at,
					'start_at_timestamp' => $subscriptionResponse->start_at,
					'end_at_timestamp' => $subscriptionResponse->end_at,
					'total_count' => $subscriptionResponse->total_count,
					'paid_count' => $subscriptionResponse->paid_count,
					'remaining_count' => $subscriptionResponse->remaining_count,
				];
				PlutusSubscription::where(['rzp_subscription_id' => $subscriptionId])->update($updateData);

				$checkSubscriptionLog = PlutusSubscriptionLog::where([
					'rzp_subscription_id' => $subscriptionId,
					'status' => $subscriptionResponse->status,
				])->exists();
				$checkSubscriptionLog = PlutusSubscriptionLog::where(['rzp_subscription_id' => $subscriptionResponse->id, 'status' => $subscriptionResponse->status])->exists();
				if ($checkSubscriptionLog == false) {
					$checkSubscriptionLog = PlutusSubscriptionLog::create([
						'uuid' => Str::uuid(),
						'rzp_plan_id' => $subscriptionResponse->plan_id,
						'rzp_subscription_id' => $subscriptionResponse->id,
						'rzp_customer_id' => empty($subscriptionResponse->customer_id) ? null : $subscriptionResponse->customer_id,
						'status' => $subscriptionResponse->status,
						'quantity' => $subscriptionResponse->quantity,
						'notes' => json_encode($subscriptionResponse->notes),
						'charge_at_timestamp' => $subscriptionResponse->charge_at,
						'start_at_timestamp' => $subscriptionResponse->start_at,
						'end_at_timestamp' => $subscriptionResponse->end_at,
						'total_count' => $subscriptionResponse->total_count,
						'paid_count' => $subscriptionResponse->paid_count,
						'customer_notify' => $subscriptionResponse->customer_notify,
						'addons' => empty($subscriptionResponse->addons) ? null : json_encode($subscriptionResponse->addons),
						'created_at_timestamp' => $subscriptionResponse->created_at,
						'expire_by_timestamp' => $subscriptionResponse->expire_by,
						'has_scheduled_changes' => $subscriptionResponse->has_scheduled_changes,
						'remaining_count' => $subscriptionResponse->remaining_count,
						'source' => $subscriptionResponse->source,
					]);
				}
				$response = (object) ['statusCode' => 200, 'message' => 'Subscription', 'data' => $subscription, 'error' => []];
			}
		} catch (Exception $e) {
			Log::info('==============================================================================================================================================================================');
			Log::error("Package Update Subscription Error");
			Log::error($e);
			Log::error("Package Update Subscription Error");
			Log::info('==============================================================================================================================================================================');
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => [$e->getMessage()]];
		}

		return $response;
	}
	public function stopAutoRenewal($subscriptionId, $cancelAtCycleEnd)
	{
		try {
			$cancelData = [
				'cancel_at_cycle_end' => $cancelAtCycleEnd,
			];
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/subscriptions/$subscriptionId/cancel");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($cancelData));
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
			$response = curl_exec($ch);

			curl_close($ch);
			Log::info('==============================================================================================================================================================================');
			Log::info('Package stopAutoRenewal start');
			Log::info((array) $response);
			Log::info('Package stopAutoRenewal end');
			Log::info('==============================================================================================================================================================================');

			$subscriptionResponse = json_decode($response);
			$checkSubscription = PlutusSubscription::where(['rzp_subscription_id' => $subscriptionResponse->id])->exists();
			if ($checkSubscription == true) {
				$updateSubscriptionData = [
					'current_start_timestamp' => $subscriptionResponse->current_start,
					'current_end_timestamp' => $subscriptionResponse->current_end,
					'ended_at_timestamp' => $subscriptionResponse->ended_at,
					'charge_at_timestamp' => $subscriptionResponse->charge_at,
					'start_at_timestamp' => $subscriptionResponse->start_at,
					'end_at_timestamp' => $subscriptionResponse->end_at,
					'total_count' => $subscriptionResponse->total_count,
					'paid_count' => $subscriptionResponse->paid_count,
					'remaining_count' => $subscriptionResponse->remaining_count,
				];
				PlutusSubscription::where(['rzp_subscription_id' => $subscriptionResponse->id])->update($updateSubscriptionData);

				//
				$checkSubscriptionLog = PlutusSubscriptionLog::where(['rzp_subscription_id' => $subscriptionResponse->id, 'status' => $subscriptionResponse->status])->exists();
				if ($checkSubscriptionLog == false) {
					PlutusSubscriptionLog::create([
						'uuid' => Str::uuid(),
						'rzp_plan_id' => $subscriptionResponse->plan_id,
						'rzp_subscription_id' => $subscriptionResponse->id,
						'rzp_customer_id' => empty($subscriptionResponse->customer_id) ? null : $subscriptionResponse->customer_id,
						'status' => $subscriptionResponse->status,
						'quantity' => $subscriptionResponse->quantity,
						'notes' => json_encode($subscriptionResponse->notes),
						'charge_at_timestamp' => $subscriptionResponse->charge_at,
						'start_at_timestamp' => $subscriptionResponse->start_at,
						'end_at_timestamp' => $subscriptionResponse->end_at,
						'total_count' => $subscriptionResponse->total_count,
						'paid_count' => $subscriptionResponse->paid_count,
						'customer_notify' => $subscriptionResponse->customer_notify,
						'addons' => empty($subscriptionResponse->addons) ? null : json_encode($subscriptionResponse->addons),
						'created_at_timestamp' => $subscriptionResponse->created_at,
						'expire_by_timestamp' => $subscriptionResponse->expire_by,
						'has_scheduled_changes' => $subscriptionResponse->has_scheduled_changes,
						'remaining_count' => $subscriptionResponse->remaining_count,
						'source' => $subscriptionResponse->source,
					]);
				}
			}

			$resp = (object) ['statusCode' => 200, 'message' => 'Subscription', 'data' => $subscriptionResponse, 'error' => []];
		} catch (Exception $e) {
			Log::info('==============================================================================================================================================================================');
			Log::error('Package stopAutoRenewal error');
			Log::error($e);
			Log::error('Package stopAutoRenewal error');
			Log::info('==============================================================================================================================================================================');
			$resp = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => []];
		}
		return $resp;
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


	public function createOrder($userId, $subscriptionId, $amount, $currency = 'INR', $receipt, $notes, $orderId = null)
	{
		$response = '';
		try {
			if ($this->validation()['statusCode'] === 200) {
				if ($orderId == null) {
					$orderId = 'AL' . Str::upper(Str::random(13));
				}

				// dd($orderId);
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
					'amount' => $amount,
					'currency' => $currency,
					'receipt' => $receipt,
					'notes' => $notes,
				];

				$response = ApiCall::postCall($this->orderUrl, $orderData, $this->encodeRazorKey);
				
				Log::info('==============================================================================================================================================================================');
				Log::info('Package Create Order start');
				Log::info((array) $response);
				Log::info('Package Create Order end');
				Log::info('==============================================================================================================================================================================');
				$orderResponse = json_decode($response);
				// dd($orderResponse);
				if (!empty($orderResponse->error)) {
					throw new Exception($orderResponse->error->description, Response::HTTP_BAD_REQUEST);
				}
				if (!empty($orderResponse->status) && $orderResponse->status === 'created') {
					// Update order details
					$this->orderRepository->updateOrder($order->id, $orderResponse);

					// Create new order log
					$this->orderLogRepository->createOrderLog(['rzp_order_id' => $orderResponse->id, 'status' => $orderResponse->status]);
					$orderResponse->plutus_order_id = $orderId;

					// $response = $this->apiResponse->getResponse(200, [$orderResponse], 'Order Created');
					$response = (object) ['statusCode' => 200, 'message' => 'Order Created', 'data' => $orderResponse, 'error' => []];

					// $response = $this->apiResponse->getResponse(200, [$planResponse], 'Plan Created');
				}
			} else {
				throw new Exception($this->validation()['message'], $this->validation()['statusCode']);
			}
		} catch (Exception $e) {
			// dd($e);
			Log::info('==============================================================================================================================================================================');
			Log::error('Package Create Order error');
			Log::error($e);
			Log::error('Package Create Order error');
			Log::info('==============================================================================================================================================================================');
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => $e->getCode()];
		}
		return $response;
	}

	public function verifySignature($orderId, $paymentId, $signature)
	{

		try {
			if ($this->validation()['statusCode'] === 200) {
				if (config('arhamlabs_pg.active_mode') == false) {
					$secret = config('arhamlabs_pg.razorpay_test_secret');
				} else {
					$secret = config('arhamlabs_pg.razorpay_live_secret');
				}
				$generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
				if ($generatedSignature == $signature) {
					$response = (object) ['statusCode' => 200, 'message' => 'Verified Payment', 'data' => [], 'error' => []];
				} else {
					$response = (object) ['statusCode' => 422, 'message' => 'Unverified Payment', 'data' => [], 'error' => []];
				}
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			Log::info('==============================================================================================================================================================================');
			Log::error('Package Verify Signature error');
			Log::error($e);
			Log::error('Package Verify Signature error');
			Log::info('==============================================================================================================================================================================');
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => $e->getMessage()];
		}
		return $response;
	}

	public function payment($paymentId, $subscriptionId = null)
	{
		try {
			if ($this->validation()['statusCode'] === 200) {
				// Get payment details by payment id
				$response = ApiCall::getCall($this->paymentUrl, $paymentId, $this->encodeRazorKey);

				$jsonData = json_decode($response);

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

					$checkPayment = PlutusPayment::where('rzp_order_id', $orderId)->exists();

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
							'notes' => json_decode(json_encode($notes), true, JSON_UNESCAPED_SLASHES),
							//json_encode($notes),
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
							'acquirer_data' => json_decode(json_encode($acquirerData), true, JSON_UNESCAPED_SLASHES),
							//json_encode($acquirerData),
							'created_at' => (string) $createdAtTimestamp,
							// 'createdAtTimestamp' => (string) $createdAtTimestamp,
						];
						Log::info("paymentResult object");
						Log::info($paymentResult);
						$this->paymentRepository->createPayment((object) $paymentResult);
						$this->updatePaymentStatus($paymentId);
						if (!empty(config('arhamlabs_pg.allow_capture_payment')) && config('arhamlabs_pg.allow_capture_payment') == true) {
							$this->capturePayment($paymentId, $amount, $orderId);
						}
					}
					// else {
					// 	throw new Exception("Payment already exists for the specified payment id $paymentId", Response::HTTP_UNPROCESSABLE_ENTITY);
					// }
					$this->updateOrderStatus($orderId, $subscriptionId);
					$this->updatePaymentStatus($paymentId);
					// $response = $this->apiResponse->getResponse(200, array('order_id' => $orderId, 'payment' => $jsonData)); // , '','','', [$capturePayment]
					$response = (object) ['statusCode' => 200, 'message' => '', 'data' => (object) ['order_id' => $orderId, 'payment' => $jsonData], 'error' => []];
					// dd('if',$response);
					Log::error('Package Payment IF error');
				} else {
					// dd($orderId, $subscriptionId);
					$this->updateOrderStatus($orderId, $subscriptionId);

					$this->updatePaymentStatus($paymentId);
					// $response = $this->apiResponse->getResponse(200, array('order_id' => $orderId, 'payment' => $jsonData)); // , '','','', [$capturePayment]
					$response = (object) ['statusCode' => 200, 'message' => '', 'data' => (object) ['order_id' => $orderId, 'payment' => $jsonData], 'error' => []];
					// throw new Exception("Order could not be found for the specified payment id $paymentId", Response::HTTP_UNPROCESSABLE_ENTITY);
					// dd('else',$response);
					Log::error('Package Payment ELSE error');
				}
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			// dd($e);

			Log::info('==============================================================================================================================================================================');
			Log::error('Package Payment error');
			Log::error($e);
			Log::error('Package Payment error');
			Log::info('==============================================================================================================================================================================');
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => $e->getMessage()];
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
					'amount' => $amount,
				];
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$response = curl_exec($ch);
				curl_close($ch);

				Log::info('==============================================================================================================================================================================');
				Log::error('Package Capture Payment Data');
				Log::info((array) $response);
				Log::error('Package Capture Payment Data');
				Log::info('==============================================================================================================================================================================');
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
			Log::info('==============================================================================================================================================================================');
			Log::error('Package Capture Payment error');
			Log::error($e);
			Log::error('Package Capture Payment error');
			Log::info('==============================================================================================================================================================================');
			$response = $this->apiResponse->getResponse($e->getCode(), [], $e->getMessage());
		}
		return $response;
	}

	public function updateOrderStatus($orderId, $subscriptionId = null)
	{
		$checkOrder = PlutusOrder::where('rzp_order_id', $orderId)->exists();
		// dd($checkOrder);
		if ($checkOrder == true) {
			// dd('if');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/orders/$orderId");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
			$response = curl_exec($ch);
			curl_close($ch);

			Log::info('==============================================================================================================================================================================');
			Log::error('Package Update Order Status');
			Log::info((array) $response);
			Log::error('Package Update Order Status');
			Log::info('==============================================================================================================================================================================');
			$jsonData = json_decode($response);
			$status = $jsonData->status;

			$checkOrderLogs = PlutusOrderLog::where(['rzp_order_id' => $orderId, 'status' => $status])->exists();
			if ($checkOrderLogs == false) {
				$plutusOrderLog = new PlutusOrderLog();
				$plutusOrderLog->uuid = Str::uuid();
				$plutusOrderLog->rzp_order_id = $orderId;
				$plutusOrderLog->status = $status;
				$plutusOrderLog->save();
			}
			PlutusOrder::where('rzp_order_id', $orderId)->update(['status' => $status]);
			$response = ['status' => $status];
		} else {
			// dd('else');
			$order = $this->orderRepository->getOrderByOrderId($this->orderUrl, $orderId, $this->encodeRazorKey);
			$orderResult = json_decode($order);
			// dd($orderResult);
			$plutusSubscription = PlutusSubscription::select('notes')
				->where('rzp_subscription_id', $subscriptionId)
				->first();
			// dd($plutusSubscription);
			$plutusOrderId = 'AL' . Str::upper(Str::random(13));
			if (!empty($plutusSubscription)) {
				if (!empty($plutusSubscription['notes'])) {
					$notes = json_decode($plutusSubscription['notes'], true);
					if (!empty($notes) && !empty($notes['plutus_order_id'])) {
						$plutusOrderId = $notes['plutus_order_id'];
					} else {
						$plutusOrderId = 'AL' . Str::upper(Str::random(13));
					}
				}
			}
			// dd($plutusOrderId);
			$createOrderData = [
				'user_id' => null,
				'rzp_subscription_id' => $this->checkEmptyString($subscriptionId),
				'rzp_order_id' => $orderResult->id,
				'order_id' => $plutusOrderId,
				'amount' => $orderResult->amount,
				'currency' => $orderResult->currency,
				'receipt' => $orderResult->receipt,
				'notes' => json_encode($orderResult->notes),
				'status' => $orderResult->status,
			];

			// Add data in our database
			$order = $this->orderRepository->createOrder($createOrderData);
			$response = ['status' => $orderResult->status];
		}
		return $response;
	}

	public function getOrderByOrderId($orderId)
	{
		return PlutusOrder::where('rzp_order_id', $orderId)->first();
	}

	public function getPaymentByPaymentId($paymentId)
	{
		return PlutusPayment::where('rzp_payment_id', $paymentId)->first();
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
			Log::info('==============================================================================================================================================================================');
			Log::error('Package Update Paymet Status');
			Log::info((array) $response);
			Log::error('Package Update Paymet Status');
			Log::info('==============================================================================================================================================================================');

			curl_close($ch);
			$jsonData = json_decode($response);
			$status = $jsonData->status;
			$checkPaymentLogs = PlutusPaymentLog::where(['rzp_payment_id' => $paymentId, 'status' => $status])->exists();
			if ($checkPaymentLogs == false) {
				$plutusPaymentLog = new PlutusPaymentLog();
				$plutusPaymentLog->uuid = Str::uuid();
				$plutusPaymentLog->rzp_payment_id = $paymentId;
				$plutusPaymentLog->status = $status;
				$plutusPaymentLog->save();
			}
			PlutusPayment::where('rzp_payment_id', $paymentId)->update(['status' => $status]);
			$response = ['status' => $status];
			// return true;
		} else {
			$payment = $this->paymentRepository->getPaymentByPaymentId($this->paymentUrl, $paymentId, $this->encodeRazorKey);
			$paymentResult = json_decode($payment);
			// $paymentResult->orderId = $paymentResult->order_id;
			$paymentResult->payment_id = $paymentResult->id;
			$status = $paymentResult->status;
			// dd($paymentResult, $status);
			$checkPaymentLogs = PlutusPaymentLog::where(['rzp_payment_id' => $paymentId, 'status' => $status])->exists();
			if ($checkPaymentLogs == false) {
				$plutusPaymentLog = new PlutusPaymentLog();
				$plutusPaymentLog->uuid = Str::uuid();
				$plutusPaymentLog->rzp_payment_id = $paymentId;
				$plutusPaymentLog->status = $status;
				$plutusPaymentLog->save();
			}
			$this->paymentRepository->createPayment($paymentResult);
			$response = ['status' => $status];
		}
		return $response;
	}

	public function webhook()
	{
		$data = file_get_contents('php://input');
		if (!empty($data)) {
			$jsonData = json_decode($data);
			if ($jsonData->event == 'subscription.charged') {
				$this->subscriptionCharged($data);
			}
			if ($jsonData->event == 'subscription.cancelled') {
				$this->subscriptionCancelled($data);
			}
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
		return $this->apiResponse->getResponse(200, [], 'Webhook Start');
	}

	public function paymentAuthorized($data)
	{
		Log::info('==============================================================================================================================================================================');
		Log::info('Payment Authorized Start');
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

		$razorpayWebhook = new RazorpayWebhook();
		$razorpayWebhook->uuid = Str::uuid();
		$razorpayWebhook->event = 'payment.authorized';
		$razorpayWebhook->order_id = $orderId;
		$razorpayWebhook->payment_id = $paymentId;
		$razorpayWebhook->payload = $data;
		$razorpayWebhook->rzp_created_at = $createdAtTimestamp;

		$razorpayWebhook->save();

		Log::info($status);
		$checkPayment = PlutusPayment::where('rzp_payment_id', $paymentId)->exists();
		$order = PlutusOrder::select('id')
			->where('rzp_order_id', $orderId)
			->first();

		if ($checkPayment == true) {

			Log::info("$paymentId exists");

			PlutusPayment::where('rzp_payment_id', $paymentId)->update(['status' => $status]);
		} else {
			Log::info("$paymentId not exists");
			$order = PlutusOrder::select('id')
				->where('rzp_order_id', $orderId)
				->first();
			// dd('reach', $order->id);
			if (!empty($order)) {
				PlutusPayment::insertOrIgnore([
					'uuid' => Str::uuid(),
					'rzp_order_id' => $orderId,
					'rzp_payment_id' => $paymentId,
					'amount' => $amount,
					'currency' => $currency,
					'status' => $status,
					'rzp_invoice_id' => $invoiceId,
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
					'rzp_token_id' => $tokenId,
					'notes' => json_encode($notes),
					'fee' => $fee,
					'tax' => $tax,
					'error_code' => $errorCode,
					'error_description' => $errorDescription,
					'error_source' => $errorSource,
					'error_step' => $errorStep,
					'error_reason' => $errorReason,
					'acquirer_data' => json_encode($acquirerData),
					'rzp_created_at' => (string) $createdAtTimestamp,
					'created_at' => Carbon::now(),
				]);
			}
		}
		$checkOrderLogs = PlutusPaymentLog::where(['rzp_payment_id' => $paymentId, 'status' => $status])->exists();
		if ($checkOrderLogs == false) {
			$plutusPaymentLog = new PlutusPaymentLog();
			$plutusPaymentLog->uuid = Str::uuid();
			$plutusPaymentLog->rzp_payment_id = $paymentId;
			$plutusPaymentLog->status = $status;
			$plutusPaymentLog->save();
		}

		Log::info('Payment Authorized End');
		Log::info('==============================================================================================================================================================================');

	}

	public function paymentCaptured($data)
	{
		Log::info('==============================================================================================================================================================================');
		Log::info('Payment Captured Start');


		Log::info($data);

		$jsonData = json_decode($data);
		$jsonData = $jsonData->payload->payment->entity;
		$paymentId = $jsonData->id;
		$orderId = $jsonData->order_id;
		$status = $jsonData->status;
		$createdAtTimestamp = $jsonData->created_at;
		$razorpayWebhook = new RazorpayWebhook();
		$razorpayWebhook->uuid = Str::uuid();
		$razorpayWebhook->event = 'payment.captured';
		$razorpayWebhook->order_id = $orderId;
		$razorpayWebhook->payment_id = $paymentId;
		$razorpayWebhook->payload = $data;
		$razorpayWebhook->rzp_created_at = $createdAtTimestamp;
		$razorpayWebhook->save();

		Log::info($status);

		$checkPayment = PlutusPayment::where('rzp_payment_id', $paymentId)->exists();
		// dd($checkPayment);
		if ($checkPayment == true) {

			Log::info("$paymentId exists");

			PlutusPayment::where('rzp_payment_id', $paymentId)->update(['status' => $status]);
		}

		$checkPaymentLogs = PlutusPaymentLog::where(['rzp_payment_id' => $paymentId, 'status' => $status])->exists();
		// dd($checkPaymentLogs);
		if ($checkPaymentLogs == false) {
			$plutusPaymentLog = new PlutusPaymentLog();
			$plutusPaymentLog->uuid = Str::uuid();
			$plutusPaymentLog->rzp_payment_id = $paymentId;
			$plutusPaymentLog->status = $status;
			$plutusPaymentLog->save();
		}

		Log::info('Payment Captured End');
		Log::info('==============================================================================================================================================================================');
	}

	public function paymentFailed($data)
	{
		Log::info('==============================================================================================================================================================================');
		Log::info('Payment Failed Start');
		Log::info($data);
		$jsonData = json_decode($data);
		$jsonData = $jsonData->payload->payment->entity;
		$paymentId = $jsonData->id;
		$orderId = $jsonData->order_id;
		$status = $jsonData->status;
		$createdAtTimestamp = $jsonData->created_at;
		$razorpayWebhook = new RazorpayWebhook();
		$razorpayWebhook->uuid = Str::uuid();
		$razorpayWebhook->event = 'payment.failed';
		$razorpayWebhook->order_id = $orderId;
		$razorpayWebhook->payment_id = $paymentId;
		$razorpayWebhook->payload = $data;
		$razorpayWebhook->rzp_created_at = $createdAtTimestamp;
		$razorpayWebhook->save();

		Log::info($status);

		$checkPayment = PlutusPayment::where('rzp_payment_id', $paymentId)->exists();
		// dd($checkPayment);
		if ($checkPayment == true) {

			Log::info("$paymentId exists");

			PlutusPayment::where('rzp_payment_id', $paymentId)->update(['status' => $status]);
		}
		$checkPaymentLogs = PlutusPaymentLog::where(['rzp_payment_id' => $paymentId, 'status' => $status])->exists();

		if ($checkPaymentLogs == false) {
			$plutusPaymentLog = new PlutusPaymentLog();
			$plutusPaymentLog->uuid = Str::uuid();
			$plutusPaymentLog->rzp_payment_id = $paymentId;
			$plutusPaymentLog->status = $status;
			$plutusPaymentLog->save();
		}
		Log::info('Payment Failed End');
		Log::info('==============================================================================================================================================================================');
	}

	public function subscriptionCharged($data)
	{
		Log::info('==============================================================================================================================================================================');
		Log::info('Subscription Charged Start');
		Log::info($data);
		// exit;
		$jsonData = json_decode($data);
		$subscriptionJsonData = $jsonData->payload->subscription->entity;
		$subscriptionId = $subscriptionJsonData->id;
		$planId = $subscriptionJsonData->plan_id;
		$subscriptionStatus = $subscriptionJsonData->status;
		$currentStart = $subscriptionJsonData->current_start;
		$currentEnd = $subscriptionJsonData->current_end;
		$endedAt = $subscriptionJsonData->ended_at;
		$notes = $subscriptionJsonData->notes;
		// dd($notes);
		$chargeAt = $subscriptionJsonData->charge_at;
		$startAt = $subscriptionJsonData->start_at;
		$endAt = $subscriptionJsonData->end_at;
		$remainingCount = $subscriptionJsonData->remaining_count;
		$paidCount = $subscriptionJsonData->paid_count;
		$totalCount = $subscriptionJsonData->total_count;

		$paymentJsonData = $jsonData->payload->payment->entity;
		$paymentId = $paymentJsonData->id;
		$paymentStatus = $paymentJsonData->status;
		$orderId = $paymentJsonData->order_id;
		// dd($orderId, $this->updateOrderStatus($orderId));
		$amount = $paymentJsonData->amount;
		$notes = $paymentJsonData->notes;
		$receipt = empty($paymentJsonData->receipt) ? null : $paymentJsonData->receipt;
		$createdAtTimestamp = $jsonData->created_at;
		$razorpayWebhook = new RazorpayWebhook();
		$razorpayWebhook->uuid = Str::uuid();
		$razorpayWebhook->event = 'subscription.charged';
		$razorpayWebhook->order_id = $orderId;
		$razorpayWebhook->payment_id = $paymentId;
		$razorpayWebhook->subscription_Id = $subscriptionId;
		$razorpayWebhook->payload = $data;
		$razorpayWebhook->rzp_created_at = $createdAtTimestamp;
		$razorpayWebhook->save();
		DB::enableQueryLog();

		// Add data in order table
		if (!empty($subscriptionJsonData->notes) && !empty($subscriptionJsonData->notes->plutus_order_id)) {
			$plutusOrderId = $subscriptionJsonData->notes->plutus_order_id;
			$checkOrderLogs = PlutusOrderLog::where(['rzp_order_id' => $orderId, 'status' => $this->updateOrderStatus($orderId)])->exists();
			if ($checkOrderLogs == false) {
				$plutusOrderLog = new PlutusOrderLog();
				$plutusOrderLog->uuid = Str::uuid();
				$plutusOrderLog->rzp_order_id = $orderId;
				$plutusOrderLog->status = $this->updateOrderStatus($orderId);
				$plutusOrderLog->save();
			}
			$checkOrder = PlutusOrder::where(['order_id' => $plutusOrderId])->exists();
			if ($checkOrder == true) {
				PlutusOrder::where(['order_id' => $plutusOrderId])->update([
					'status' => $this->updateOrderStatus($orderId),
				]);
			} else {
				// dd('insert', $this->paymentController->getPayment($paymentId, $subscriptionId));
			}
		}

		$checkSubscription = PlutusSubscription::where('rzp_subscription_id', $subscriptionId)->exists();
		if (!empty($checkSubscription) || $checkSubscription == true) {
			PlutusSubscription::where('rzp_subscription_id', $subscriptionId)->update([
				'status' => $subscriptionStatus,
				'current_start_timestamp' => $currentStart,
				'current_end_timestamp' => $currentEnd,
				'charge_at_timestamp' => $chargeAt,
				'start_at_timestamp' => $startAt,
				'end_at_timestamp' => $endAt,
				'total_count' => $totalCount,
				'paid_count' => $paidCount,
				'remaining_count' => $remainingCount,
			]);

			// Order
			// $this->createOrder(null, $subscriptionId, $amount, $currency = "INR", $receipt, $notes);
			// Payment
			// $this->payment($paymentId);
			// $this->updateOrderStatus($orderId);
			// $this->updatePaymentStatus($paymentId);
		}
		Log::info('subscription status ' . $subscriptionStatus);
		Log::info('subscription id ' . $subscriptionId);
		Log::info('check subscription ' . $checkSubscription);
		Log::info(DB::getQueryLog());
		Log::info('Subscription Charged End');
		Log::info('==============================================================================================================================================================================');
	}

	public function subscriptionCancelled($data)
	{
		Log::info('==============================================================================================================================================================================');
		Log::info('Subscription cancelled webhook start');
		Log::info($data);

		$jsonData = json_decode($data);
		$subscriptionJsonData = $jsonData->payload->subscription->entity;
		$subscriptionId = $subscriptionJsonData->id;
		$planId = $subscriptionJsonData->plan_id;
		$subscriptionStatus = $subscriptionJsonData->status;
		$currentStart = $subscriptionJsonData->current_start;
		$currentEnd = $subscriptionJsonData->current_end;
		$endedAt = $subscriptionJsonData->ended_at;
		$notes = $subscriptionJsonData->notes;
		$chargeAt = $subscriptionJsonData->charge_at;
		$startAt = $subscriptionJsonData->start_at;
		$endAt = $subscriptionJsonData->end_at;
		$totalCount = $subscriptionJsonData->total_count;
		$paidCount = $subscriptionJsonData->paid_count;
		$remainingCount = $subscriptionJsonData->remaining_count;

		$createdAtTimestamp = $jsonData->created_at;
		$subscriptionResponse = $subscriptionJsonData;
		DB::enableQueryLog();
		$checkRazorpayWebhook = RazorpayWebhook::where([
			'subscription_id' => $subscriptionId,
			'event' => 'subscription.cancelled',
		])->exists();
		if ($checkRazorpayWebhook == false) {
			$razorpayWebhook = new RazorpayWebhook();
			$razorpayWebhook->uuid = Str::uuid();
			$razorpayWebhook->event = 'subscription.cancelled';
			$razorpayWebhook->order_id = null;
			$razorpayWebhook->payment_id = null;
			$razorpayWebhook->subscription_Id = $subscriptionId;
			$razorpayWebhook->payload = json_encode(json_decode($data, JSON_UNESCAPED_SLASHES));
			$razorpayWebhook->rzp_created_at = $createdAtTimestamp;
			$razorpayWebhook->save();
		}
		$checkSubscription = PlutusSubscription::where('rzp_subscription_id', $subscriptionId)->exists();
		if (!empty($checkSubscription) || $checkSubscription == true) {
			$where = ['rzp_subscription_id' => $subscriptionId];
			$updateData = [
				'status' => $subscriptionStatus,
				'current_start_timestamp' => $currentStart,
				'current_end_timestamp' => $currentEnd,
				'charge_at_timestamp' => $chargeAt,
				'start_at_timestamp' => $startAt,
				'end_at_timestamp' => $endAt,
				'total_count' => $totalCount,
				'paid_count' => $paidCount,
				'remaining_count' => $remainingCount,
			];
			// $this->subscriptionRepository->update($where, $updateData);
			PlutusSubscription::where($where)->update([
				'status' => $updateData['status'],
				'current_start_timestamp' => $updateData['current_start_timestamp'],
				'current_end_timestamp' => $updateData['current_end_timestamp'],
				'charge_at_timestamp' => $updateData['charge_at_timestamp'],
				'start_at_timestamp' => $updateData['start_at_timestamp'],
				'end_at_timestamp' => $updateData['end_at_timestamp'],
				'total_count' => $updateData['total_count'],
				'paid_count' => $updateData['paid_count'],
				'remaining_count' => $updateData['remaining_count'],
			]);
			// dd('reach', $where, $updateData);
		} else {
			PlutusSubscription::create([
				'uuid' => Str::uuid(),
				'rzp_plan_id' => $subscriptionResponse->plan_id,
				'rzp_subscription_id' => $subscriptionResponse->id,
				'rzp_customer_id' => empty($subscriptionResponse->customer_id) ? null : $subscriptionResponse->customer_id,
				'status' => $subscriptionResponse->status,
				'quantity' => $subscriptionResponse->quantity,
				'notes' => json_encode($subscriptionResponse->notes),
				'charge_at_timestamp' => $subscriptionResponse->charge_at,
				'start_at_timestamp' => $subscriptionResponse->start_at,
				'end_at_timestamp' => $subscriptionResponse->end_at,
				'total_count' => $subscriptionResponse->total_count,
				'paid_count' => $subscriptionResponse->paid_count,
				'customer_notify' => $subscriptionResponse->customer_notify,
				'addons' => empty($subscriptionResponse->addons) ? null : json_encode($subscriptionResponse->addons),
				'created_at_timestamp' => $subscriptionResponse->created_at,
				'expire_by_timestamp' => $subscriptionResponse->expire_by,
				'has_scheduled_changes' => $subscriptionResponse->has_scheduled_changes,
				'remaining_count' => $subscriptionResponse->remaining_count,
				'source' => $subscriptionResponse->source,
			]);
		}
		// dd($checkRazorpayWebhook, DB::getQueryLog());
		Log::info('Subscription cancelled webhook end');
		Log::info('==============================================================================================================================================================================');
	}

	public function refund($paymentId, $amount, $notes)
	{
		Log::info('==============================================================================================================================================================================');
		try {
			if ($this->validation()['statusCode'] == 200) {
				if (config('arhamlabs_pg.allow_refund') == false || config('arhamlabs_pg.allow_refund') == null) {
					throw new Exception('Please set allow_refund to true in your .env file', 422);
				}

				$refundData = [
					'amount' => $amount,
					'notes' => $notes,
				];
				// dd($refundData);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/payments/$paymentId/refund");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($refundData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$response = curl_exec($ch);
				Log::error('Package Refund Data');
				Log::error((array) $response);
				Log::error('Package Refund Data');
				curl_close($ch);
				$jsonData = json_decode($response);
				if (!empty($jsonData->error)) {
					throw new Exception($jsonData->error->description, Response::HTTP_BAD_REQUEST);
				} else {
					$plutusRefund = new PlutusRefund();
					$plutusRefund->uuid = Str::uuid();
					$plutusRefund->rzp_refund_id = $jsonData->id;
					$plutusRefund->amount = $amount;
					$plutusRefund->currency = 'INR';
					$plutusRefund->rzp_payment_id = $paymentId;
					$plutusRefund->notes = json_encode($notes);
					$plutusRefund->acquirer_data = json_encode($jsonData->acquirer_data);
					$plutusRefund->created_at_timestamp = $jsonData->created_at;
					$plutusRefund->batch_id = $jsonData->batch_id;
					$plutusRefund->status = $jsonData->status;
					$plutusRefund->speed_processed = $jsonData->speed_processed;
					$plutusRefund->speed_requested = $jsonData->speed_requested;
					$plutusRefund->save();

					$response = (object) ['statusCode' => 200, 'message' => 'Refund Data', 'data' => $jsonData, 'error' => []];
				}



			} else {
				throw new Exception($this->validation()['message'], $this->validation()['statusCode']);
			}
		} catch (Exception $e) {

			Log::info('==============================================================================================================================================================================');
			Log::error('Package Refund Error');
			Log::error($e);
			Log::error('Package Refund Error');
			Log::info('==============================================================================================================================================================================');
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => $e->getMessage()];
		}
		Log::info('==============================================================================================================================================================================');
		return $response;
	}

	public function cancel_subscription($subscriptionId, $cancelAtCycleEnd)
	{
		Log::info('==============================================================================================================================================================================');
		try {
			$cancelData = [
				'cancel_at_cycle_end' => $cancelAtCycleEnd,
			];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/subscriptions/$subscriptionId/cancel");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($cancelData));
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
			$cancelResponse = curl_exec($ch);
			// curl_close($ch);

			// Get Latest data
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/subscriptions/$subscriptionId");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
			$response = curl_exec($ch);


			curl_close($ch);
			$subscriptionResponse = json_decode($response, JSON_UNESCAPED_SLASHES);
			Log::error("Package Cancel Subscription Data");
			Log::error((array) $subscriptionResponse);
			Log::error("Package Cancel Subscription Data");

			// dd($subscriptionResponse['ended_at']);
			if (!empty($subscriptionResponse->error)) {
				$response = (object) ['statusCode' => 422, 'message' => $subscriptionResponse->error->description, 'data' => [], 'error' => $subscriptionResponse];
			} else {
				$checkSubscription = PlutusSubscription::where('rzp_subscription_id', $subscriptionId)->exists();
				if ($checkSubscription == true) {
					PlutusSubscription::where('rzp_subscription_id', $subscriptionId)->update(['status' => $subscriptionResponse['status'], 'ended_at_timestamp' => $subscriptionResponse['ended_at']]);
				}
				$checkSubscriptionLog = PlutusSubscriptionLog::where(['rzp_subscription_id' => $subscriptionId, 'status' => $subscriptionResponse['status']])->exists();
				if ($checkSubscriptionLog == false) {
					PlutusSubscriptionLog::create([
						'uuid' => Str::uuid(),
						'rzp_plan_id' => $subscriptionResponse['plan_id'],
						'rzp_subscription_id' => $subscriptionResponse['id'],
						'rzp_customer_id' => empty($subscriptionResponse['customer_id']) ? null : $subscriptionResponse['customer_id'],
						'status' => $subscriptionResponse['status'],
						'ended_at_timestamp' => $subscriptionResponse['ended_at'],
						'quantity' => $subscriptionResponse['quantity'],
						'notes' => json_encode($subscriptionResponse['notes']),
						'charge_at_timestamp' => $subscriptionResponse['charge_at'],
						'start_at_timestamp' => $subscriptionResponse['start_at'],
						'end_at_timestamp' => $subscriptionResponse['end_at'],
						'total_count' => $subscriptionResponse['total_count'],
						'paid_count' => $subscriptionResponse['paid_count'],
						'customer_notify' => $subscriptionResponse['customer_notify'],
						'addons' => empty($subscriptionResponse['addons']) ? null : json_encode($subscriptionResponse['addons']),
						'created_at_timestamp' => $subscriptionResponse['created_at'],
						'expire_by_timestamp' => $subscriptionResponse['expire_by'],
						'has_scheduled_changes' => $subscriptionResponse['has_scheduled_changes'],
						'remaining_count' => $subscriptionResponse['remaining_count'],
						'source' => $subscriptionResponse['source'],
					]);
				}
				$response = (object) ['statusCode' => 200, 'message' => 'Subscription Cancel', 'data' => $subscriptionResponse, 'error' => []];
			}
		} catch (Exception $e) {
			// dd($e);
			Log::info('==============================================================================================================================================================================');
			Log::error("Package Cancel Subscription Error");
			Log::error($e);
			Log::error("Package Cancel Subscription Error");
			Log::info('==============================================================================================================================================================================');
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => []];
		}
		Log::info('==============================================================================================================================================================================');
		return $response;
	}


	public function transfer($accountId, $amount)
	{
		Log::info('==============================================================================================================================================================================');
		try {
			if ($this->validation()['statusCode'] == 200) {
				if (config('arhamlabs_pg.transfer_fund') == false || config('arhamlabs_pg.transfer_fund') == null) {
					throw new Exception('Please set transfer_fund to true in your .env file', 422);
				}

				if (empty(config('arhamlabs_pg.account_id')) || config('arhamlabs_pg.account_id') == null) {
					throw new Exception('Please set account_id in your .env file', 422);
				}

				$transferUrl = "https://api.razorpay.com/v1/transfers";
				$transferData = [
					"account" => $accountId,
					//acc_KO6cypkqQIsmaH
					"amount" => $amount,
					"currency" => "INR"
				];
				$transferResponseJson = ApiCall::postCall($transferUrl, $transferData, $this->encodeRazorKey);
				$transferResponse = json_decode($transferResponseJson);

				if (!empty($transferResponse->error) && !empty($transferResponse->error->description)) {
					throw new Exception($transferResponse->error->description, Response::HTTP_BAD_REQUEST);
				}

				if (!empty($transferResponse->status)) {
					$checkPlutusTransfer = PlutusTransfer::where(['transfer_id' => $transferResponse->id])->exists();
					if ($checkPlutusTransfer == true) {
						$updateTransferData = [
							'transfer_id' => $transferResponse->id,
							'status' => $transferResponse->status,
							'source' => $transferResponse->source,
							'recipient' => $transferResponse->recipient,
							'amount' => $transferResponse->amount,
							'currency' => $transferResponse->currency,
							'amount_reversed' => $transferResponse->amount_reversed,
							'fees' => $transferResponse->fees,
							'tax' => $transferResponse->tax,
							'notes' => json_encode($transferResponse->notes),
							'linked_account_notes' => json_encode($transferResponse->linked_account_notes),
							'on_hold' => $transferResponse->on_hold,
							'on_hold_until' => $transferResponse->on_hold_until,
							'recipient_settlement_id' => $transferResponse->recipient_settlement_id,
							'rzp_created_at' => $transferResponse->created_at,
							'processed_at' => $transferResponse->processed_at,
							'error' => json_encode($transferResponse->error)
						];
						PlutusTransfer::where(['transfer_id' => $transferResponse->id])->update($updateTransferData);

					} else {
						PlutusTransfer::create([
							'uuid' => Str::uuid(),
							'tranfer_type' => "direct",
							'transfer_id' => $transferResponse->id,
							'status' => $transferResponse->status,
							'source' => $transferResponse->source,
							'recipient' => $transferResponse->recipient,
							'amount' => $transferResponse->amount,
							'currency' => $transferResponse->currency,
							'amount_reversed' => $transferResponse->amount_reversed,
							'fees' => $transferResponse->fees,
							'tax' => $transferResponse->tax,
							'notes' => json_encode($transferResponse->notes),
							'linked_account_notes' => json_encode($transferResponse->linked_account_notes),
							'on_hold' => $transferResponse->on_hold,
							'on_hold_until' => $transferResponse->on_hold_until,
							'recipient_settlement_id' => $transferResponse->recipient_settlement_id,
							'rzp_created_at' => $transferResponse->created_at,
							'processed_at' => $transferResponse->processed_at,
							'error' => json_encode($transferResponse->error),
						]);
					}
				}
				$response = (object) ['statusCode' => 200, 'message' => 'Transfer Data', 'data' => $transferResponse, 'error' => []];

			}
		} catch (Exception $e) {
			Log::info('==============================================================================================================================================================================');
			Log::error('Package Refund Error');
			Log::error($e);
			Log::error('Package Refund Error');
			Log::info('==============================================================================================================================================================================');
			$response = (object) ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'data' => [], 'error' => $e->getMessage()];
		}


		Log::info('==============================================================================================================================================================================');
		return $response;
	}
	public function webhook()
	{
		return $this->apiResponse->getResponse(200, [], 'Webhook under progress');
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
			$response = $this->apiResponse->getResponse($e->getCode(), [], $e->getMessage());
		}
		return $response;
	}
}