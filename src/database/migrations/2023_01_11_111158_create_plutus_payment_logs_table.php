<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlutusPaymentLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plutus_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            // $table->bigInteger('payment_id')->unsigned()->index();
            // $table->foreign('payment_id')->references('id')->on('pg_payments')->onDelete('cascade');
            $table->string('rzp_payment_id')->index();
            $table->string('status')->default('created');
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
        Schema::dropIfExists('plutus_payment_logs');
    }
}