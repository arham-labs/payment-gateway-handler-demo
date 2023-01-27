<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlutusOrderLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plutus_order_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            // $table->bigInteger('order_id')->unsigned()->index();
            // $table->foreign('order_id')->references('id')->on('pg_orders')->onDelete('cascade');
            $table->string('rzp_order_id')->index();
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
        Schema::dropIfExists('plutus_order_logs');
    }
}
