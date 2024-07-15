<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_plans', function (Blueprint $table) {
            $table->id();

            $table->enum('status', ['vacio', 'imcompleto', 'revision', 'activo', 'finalizado', 'rechazado'])->default('vacio');
            $table->datetime('date_request')->nullable();
            $table->datetime('date_activated')->nullable();
            $table->datetime('date_end')->nullable();
            $table->string('name');
            $table->double('cost');
            $table->double('profit')->nullable();
            $table->string('duration')->nullable();
            $table->bigInteger('duration_days')->nullable();
            $table->bigInteger('duration_months')->nullable();

            $table->bigInteger('weekdays')->nullable();

            $table->double('daily_gain')->nullable();
            $table->double('daily_gain_porcentage')->nullable();




            // liberar inversion
            // $table->double('pending_inversion')->default(0);
            // $table->double('processed_inversion')->default(0);
            // $table->double('processed_inversion_max')->default(0);


            $table->datetime('date_available_inversion')->nullable();

            $table->bigInteger('products')->nullable();
            $table->bigInteger('plan_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->nullable();

            $table->double('inversion')->nullable();
            // $table->double('total_profit')->default(0);

            $table->double('minimum_charge')->default(0);
            $table->double('processing_earnings')->default(0);
            $table->double('processed_earnings')->default(0);
            
           
            $table->double('processing_inversion')->default(0);
            $table->double('processed_inversion')->default(0);


            $table->string('transaction_number', 255)->nullable();

            // DATOS DEL PAGO
            $table->double("pay_in_dollars")->default(0);
            $table->double("total_pay_dollars")->default(0);
            $table->double("pay_in_btc")->default(0);
            $table->double("license")->default(0);
            $table->double("total_pay")->default(0);

            // BANCO
            $table->bigInteger('bank_id')->nullable();
            $table->string('nameBank')->nullable();
            $table->string('holderBank')->nullable();
            $table->bigInteger('identificationBank')->nullable();
            $table->string('typeBank')->nullable();
            $table->longText('numberAccountBank')->nullable();
            // WALLET
            $table->bigInteger('wallet_id')->nullable();
            $table->string('nameWallet')->nullable();
            $table->longText('addressWallet')->nullable();
            $table->bigInteger('coin_id')->nullable();
            $table->string('coinWallet')->default('Bitcoin');
            $table->longText('linkWallet')->nullable();
            $table->longText('observations')->nullable();


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
        Schema::dropIfExists('user_plans');
    }
}
