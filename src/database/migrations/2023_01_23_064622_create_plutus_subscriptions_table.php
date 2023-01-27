<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlutusSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plutus_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('rzp_plan_id');
            $table->string('rzp_subscription_id');
            $table->string('rzp_customer_id')->nullable();
            $table->string('status')->default('created');
            $table->integer('quantity')->nullable();
            $table->json('notes')->nullable();
            $table->string('charge_at_timestamp')->nullable();
            $table->string('rzp_offer_id')->nullable();
            $table->string('start_at_timestamp')->nullable();
            $table->string('end_at_timestamp')->nullable();
            $table->integer('total_count')->nullable();
            $table->integer('paid_count')->nullable();
            $table->boolean('customer_notify')->nullable();
            $table->json('addons')->nullable();
            $table->string('created_at_timestamp')->nullable();
            $table->string('expire_by_timestamp')->nullable();
            $table->boolean('has_scheduled_changes')->nullable();
            $table->string('change_scheduled_at_timestamp')->nullable();
            $table->integer('remaining_count')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->unique(['rzp_plan_id', 'rzp_subscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plutus_subscriptions');
    }
}
