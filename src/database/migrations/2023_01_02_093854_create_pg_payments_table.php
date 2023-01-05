<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePgPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pg_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->bigInteger('order_id')->unsigned()->index();
            $table->foreign('order_id')->references('id')->on('pg_orders')->onDelete('cascade');
            $table->string('payment_id');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency')->nullable();
            $table->string('status')->default('created');
            $table->string('invoice_id')->nullable();
            $table->boolean('international')->nullable();
            $table->string('method')->nullable();
            $table->decimal('amount_refunded', 10, 2)->default(0);
            $table->string('refund_status')->nullable();
            $table->boolean('captured')->default(false);
            $table->string('description')->nullable();
            $table->string('card_id')->nullable();
            $table->string('bank')->nullable();
            $table->string('wallet')->nullable();
            $table->string('vpa')->nullable();
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            // $table->string('customer_id')->nullable();
            $table->string('token_id')->nullable();
            $table->json('notes')->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_description')->nullable();
            $table->string('error_source')->nullable();
            $table->string('error_step')->nullable();
            $table->string('error_reason')->nullable();
            $table->json('acquirer_data')->nullable();
            $table->string('created_at_timestamp')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'payment_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pg_payments');
    }
}