<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePgPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pg_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->bigInteger('user_id')->nullable();
            $table->string('rzp_plan_id');
            $table->string('period');
            $table->string('interval');
            $table->json('item');
            $table->json('notes');
            $table->string('created_at_timestamp')->nullable();
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
        Schema::dropIfExists('pg_plans');
    }
}