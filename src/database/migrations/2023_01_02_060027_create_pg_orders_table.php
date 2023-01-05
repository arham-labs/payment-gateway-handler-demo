<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePgOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pg_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->bigInteger('user_id')->nullable();
            $table->string('order_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency');
            $table->string('receipt')->nullable();
            $table->string('offer_id')->nullable();
            $table->json('notes');
            $table->string('status');
            $table->string('created_at_timestamp')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pg_orders');
    }
}