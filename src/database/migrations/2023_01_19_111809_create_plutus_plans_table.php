<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlutusPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plutus_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->bigInteger('user_id')->nullable();
            $table->string('rzp_plan_id');
            $table->string('period');
            $table->string('interval');
            $table->json('item');
            $table->json('notes');
            $table->string('rzp_created_at')->nullable();
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
        Schema::dropIfExists('plutus_plans');
    }
}
