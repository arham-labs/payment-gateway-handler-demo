<?php
use Arhamlabs\PaymentGateway\Http\Controllers\RazorpayController;

Route::get('/test', [RazorpayController::class, 'validation']);


Route::get('/callCreateOrder', [RazorpayController::class, 'callCreateOrder']);
Route::get('/verify-signature', [RazorpayController::class, 'verifySignature']);
Route::get('/callPayment', [RazorpayController::class, 'callPayment']);
Route::get('/capture-payment', [RazorpayController::class, 'capturePayment']);

Route::get('update-order-status', [RazorpayController::class, 'updateOrderStatus']);
Route::get('update-payment-status', [RazorpayController::class, 'updatePaymentStatus']);

