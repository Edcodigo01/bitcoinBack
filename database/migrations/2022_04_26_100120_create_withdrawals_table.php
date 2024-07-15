<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->enum('status',['incompleto','revision','aprobado','rechazado'])->default('incompleto');
            $table->enum('type',['investment','earnings','earnings-referralls']);
            $table->datetime('date_request')->nullable();
            $table->datetime('date_response')->nullable();
            $table->double('request');
            $table->double('commission');
            $table->double('withdraw');
            $table->double('commissionPorcentage');
            
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->nullable();

            $table->string('code')->nullable();
            $table->datetime('limit_time_code')->nullable();
            // BANCO
            // $table->string('nameBank')->nullable();
            // $table->string('holderBank')->nullable();
            // $table->bigInteger('identificationBank')->nullable();
            // $table->string('typeBank')->nullable();
            // $table->longText('numberAccountBank')->nullable();
            // WALLET
            $table->integer('coin_id')->nullable();
            $table->integer('wallet_id')->nullable();
            $table->string('wallet_name')->nullable();
            $table->string('wallet_coin')->nullable();
            $table->longText('wallet_address')->nullable();
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
        Schema::dropIfExists('withdrawals');
    }
}
