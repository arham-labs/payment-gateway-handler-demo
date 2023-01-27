<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlutusOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plutus_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('order_id');
            $table->bigInteger('user_id')->nullable();
            $table->string('rzp_subscription_id')->nullable();
            $table->string('rzp_order_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency');
            $table->string('receipt')->nullable();
            $table->string('rzp_offer_id')->nullable();
            $table->json('notes');
            $table->string('status');
            $table->string('rzp_created_at')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'user_id', 'rzp_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plutus_orders');
    }
}
