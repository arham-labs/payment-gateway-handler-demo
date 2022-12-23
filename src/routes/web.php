<?php
use Arhamlabs\PaymentGateway\Http\Controllers\RazorpayController;


Route::get('/test', [RazorpayController::class, 'validation']);


Route::get('/callCreateOrder', [RazorpayController::class, 'callCreateOrder']);