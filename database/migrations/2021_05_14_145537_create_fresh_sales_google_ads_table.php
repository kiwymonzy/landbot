<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFreshSalesGoogleAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('freshgoogleadslink', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('account_manager');
            $table->string('industry')->nullable();
            $table->integer('mcc_id')->nullable();
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
        Schema::dropIfExists('freshgoogleadslink');
    }
}
