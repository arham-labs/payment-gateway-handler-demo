<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlutusRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plutus_refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('rzp_refund_id');
            $table->decimal('amount', 14, 2);
            $table->string('currency');
            $table->string('rzp_payment_id');
            $table->json('notes');
            $table->string('receipt')->nullable();
            $table->json('acquirer_data');
            $table->string('created_at_timestamp');
            $table->string('batch_id')->nullable();
            $table->string('status');
            $table->string('speed_processed');
            $table->string('speed_requested');
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
        Schema::dropIfExists('plutus_refunds');
    }
}