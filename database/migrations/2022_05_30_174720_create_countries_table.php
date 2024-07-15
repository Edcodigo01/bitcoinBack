<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
           
            $table->string('PaisCodigo'); 
            $table->string('PaisNombre'); 
            $table->string('PaisContinente'); 
            $table->string('PaisRegion'); 
            $table->string('PaisArea'); 
            $table->string('PaisIndependencia');
            $table->string('PaisPoblacion'); 
            $table->string('PaisExpectativaDeVida');
            $table->string('PaisProductoInternoBruto');
            $table->string('PaisProductoInternoBrutoAntiguo');
            $table->string('PaisNombreLocal'); 
            $table->string('PaisGobierno');
            $table->string('PaisJefeDeEstado'); 
            $table->string('PaisCapital');
            $table->string('PaisCodigo2');
              
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
        Schema::dropIfExists('countries');
    }
}
