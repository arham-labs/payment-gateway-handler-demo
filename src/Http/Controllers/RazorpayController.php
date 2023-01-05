<?php

namespace Arhamlabs\PaymentGateway\Http\Controllers;


use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Arhamlabs\ApiResponse\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Arhamlabs\PaymentGateway\Models\PgOrder;
use Arhamlabs\PaymentGateway\Models\PgPayment;
use Arhamlabs\PaymentGateway\Models\PgOrderLog;
use Arhamlabs\PaymentGateway\Models\PgPaymentLog;
use Arhamlabs\PaymentGateway\traits\RazorpayConfigValidation;


class RazorpayController extends Controller
{
	use RazorpayConfigValidation;
	public $encodeRazorKey;
	public $apiResponse;
	public $orderUrl;
	public function __construct(ApiResponse $apiResponse)
	{
		$this->apiResponse = $apiResponse;
		$this->orderUrl = 'https://api.razorpay.com/v1/orders';
		if (!empty($this->validation()['id']) && !empty($this->validation()['key'])) {
			$this->encodeRazorKey = base64_encode($this->validation()['id'] . ':' . $this->validation()['key']);
		}
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


			} else {
				throw new Exception("Active mode " . config('arhamlabs_pg.errors.boolean'), Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
			$response = ['statusCode' => $e->getCode(), 'message' => $e->getMessage()];
		}
		return $response;

	}

	public function createOrder($userId, $amount, $currency = "INR", $receipt, $notes)
	{
		$response = '';
		try {
			if ($this->validation()['statusCode'] === 200) {
				// Add data in our database
				$order = new PgOrder;
				$order->uuid = Str::uuid();
				$order->user_id = $userId;
				$order->amount = $amount;
				$order->currency = $currency;
				$order->receipt = $receipt;
				$order->notes = json_encode($notes);
				$order->status = 'pending';
				$order->save();

				// Call Razorpay Order Api
				$orderData = [
					"amount" => $amount,
					"currency" => $currency,
					"receipt" => $receipt,
					"notes" => $notes
				];

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->orderUrl);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($orderData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$response = curl_exec($ch);
				curl_close($ch);
				$orderResponse = json_decode($response);

				if (!empty($orderResponse->error)) {
					$response = $this->apiResponse->getResponse(Response::HTTP_BAD_REQUEST, array(), $orderResponse->error->description);
				}
				if (!empty($orderResponse->status === "created")) {
					// Update order details
					$order = PgOrder::findOrFail($order->id);
					$order->order_id = $orderResponse->id;
					$order->offer_id = $orderResponse->offer_id;
					$order->status = $orderResponse->status;
					$order->created_at_timestamp = $orderResponse->created_at;
					$order->save();

					$pgOrderLog = new PgOrderLog;
					$pgOrderLog->uuid = Str::uuid();
					$pgOrderLog->order_id = $orderResponse->id;
					$pgOrderLog->status = $orderResponse->status;
					$pgOrderLog->save();

					$response = $this->apiResponse->getResponse(200, array('order_id' => $orderResponse->id), 'Order Created');
				}

			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
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

	public function payment($paymentId)
	{
		try {
			if ($this->validation()['statusCode'] === 200) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/payments/$paymentId");

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				// curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($orderData));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
				$response = curl_exec($ch);
				curl_close($ch);
				$jsonData = json_decode($response);
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
					$this->updatePaymentStatus($paymentId);
					if (!empty(config('arhamlabs_pg.allow_capture_payment')) && config('arhamlabs_pg.allow_capture_payment') == true) {
						$this->capturePayment($paymentId, $amount, $orderId);
					}
					$this->updateOrderStatus($orderId);
					$this->updatePaymentStatus($paymentId);
					$response = $this->apiResponse->getResponse(200, array('order_id' => $orderId, 'payment_id' => $paymentId)); // , '','','', [$capturePayment]
				} else {
					throw new Exception("Order could not be found for the specified payment id $paymentId", Response::HTTP_UNPROCESSABLE_ENTITY);
				}
			} else {
				throw new Exception($this->validation()['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} catch (Exception $e) {
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
					PgPayment::where('payment_id', $paymentId)->update(['status' => $jsonResponse->status]);
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

	public function updateOrderStatus($orderId)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/orders/$orderId");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
		$response = curl_exec($ch);
		curl_close($ch);
		$jsonData = json_decode($response);
		$status = $jsonData->status;

		$checkOrderLogs = PgOrderLog::where(['order_id' => $orderId, 'status' => $status])->exists();
		if ($checkOrderLogs == false) {
			$pgOrderLog = new PgOrderLog;
			$pgOrderLog->uuid = Str::uuid();
			$pgOrderLog->order_id = $orderId;
			$pgOrderLog->status = $status;
			$pgOrderLog->save();
		}
		PgOrder::where('order_id', $orderId)->update(['status' => $status]);
	}
	public function updatePaymentStatus($paymentId)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/payments/$paymentId");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $this->encodeRazorKey]);
		$response = curl_exec($ch);
		curl_close($ch);
		$jsonData = json_decode($response);
		$status = $jsonData->status;
		$checkOrderLogs = PgPaymentLog::where(['payment_id' => $paymentId, 'status' => $status])->exists();
		if ($checkOrderLogs == false) {
			$pgPaymentLog = new PgPaymentLog;
			$pgPaymentLog->uuid = Str::uuid();
			$pgPaymentLog->payment_id = $paymentId;
			$pgPaymentLog->status = $status;
			$pgPaymentLog->save();
		}
		PgPayment::where('payment_id', $paymentId)->update(['status' => $status]);
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
			$response = $this->apiResponse->getResponse($e->getCode(), array(), $e->getMessage());
		}
		return $response;
	}
}