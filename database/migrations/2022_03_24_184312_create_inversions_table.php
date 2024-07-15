<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInversionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

     
    public function up()
    {
        Schema::create('inversions', function (Blueprint $table) {
            $table->id();
            $table->datetime('date_start')->nullable();
            $table->datetime('date_end')->nullable();
            $table->unsignedBigInteger('user_plan_id')->nullable();
            $table->foreign('user_plan_id')->references('id')->on('user_plans');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->double('inversion')->default(0);

            $table->double('earnings_porcentage_referred_1')->default(0);
            $table->double('earnings_porcentage_referred_2')->default(0);
            $table->double('earnings_porcentage_referred_3')->default(0);

            $table->double('earnings_referred_1')->default(0);
            $table->double('earnings_referred_2')->default(0);
            $table->double('earnings_referred_3')->default(0);

            $table->enum('status',['last','other'])->default('last');
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
        Schema::dropIfExists('inversions');
    }
}
