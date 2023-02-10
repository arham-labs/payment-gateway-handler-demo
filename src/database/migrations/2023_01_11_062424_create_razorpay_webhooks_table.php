<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRazorpayWebhooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('razorpay_webhooks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('event');
            $table->string('order_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('subscription_id')->nullable();
            $table->json('payload');
            $table->string('rzp_created_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('razorpay_webhooks');
    }
}
