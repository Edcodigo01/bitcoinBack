<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('cost');
            $table->double('daily_gain');
            $table->double('weekdays');

            // $table->longText('total_profit');
            $table->string('duration')->nullable();
            $table->bigInteger('duration_days')->nullable();
            $table->bigInteger('duration_months')->nullable();
            // $table->longText('charge_limit');
            $table->bigInteger('products')->nullable();
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
        Schema::dropIfExists('plans');
    }
}
