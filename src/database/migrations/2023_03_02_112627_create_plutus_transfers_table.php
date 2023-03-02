<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlutusTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plutus_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('tranfer_type')->nullable();
            $table->string('transfer_id')->nullable();
            $table->string('status');
            $table->string('source');
            $table->string('recipient');
            $table->decimal('amount', 10, 2);
            $table->string('currency');
            $table->decimal('amount_reversed', 10, 2)->default(0);
            $table->decimal('fees', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->json('notes')->nullable();
            $table->json('linked_account_notes')->nullable();
            $table->boolean('on_hold')->default('false');
            $table->string('on_hold_until')->nullable();
            $table->string('recipient_settlement_id')->nullable();
            $table->string('rzp_created_at')->nullable();
            $table->string('processed_at')->nullable();
            $table->json('error')->nullable();
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
        Schema::dropIfExists('plutus_transfers');
    }
}