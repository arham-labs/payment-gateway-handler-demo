<?php
use App\Http\Controllers\TestController;
use Arhamlabs\PaymentGateway\Http\Controllers\RazorpayController;

Route::get('/test', [RazorpayController::class, 'validation']);
Route::get('/callCreateOrder', [RazorpayController::class, 'callCreateOrder']);
Route::get('/verify-signature', [RazorpayController::class, 'verifySignature']);
Route::get('/callPayment', [RazorpayController::class, 'callPayment']);
Route::get('/capture-payment', [RazorpayController::class, 'capturePayment']);

Route::get('update-order-status', [RazorpayController::class, 'updateOrderStatus']);
Route::get('update-payment-status', [RazorpayController::class, 'updatePaymentStatus']);

Route::get('webhook', [RazorpayController::class, 'webhook']);
Route::post('webhook', [RazorpayController::class, 'webhook']);

Route::get('/plan', [RazorpayController::class, 'plan']);
Route::post('/call-plan', [TestController::class, 'plans']);
Route::post('/call-subscriptions', [TestController::class, 'subscriptions']);
Route::get('/subscription', [RazorpayController::class, 'subscription']);

Route::get('/subscription-charged', [RazorpayController::class, 'subscriptionCharged    ']);