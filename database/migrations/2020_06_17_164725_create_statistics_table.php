<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->decimal('spendings', 20, 2, true);
            $table->decimal('cost_per_call', 20, 2, true);
            $table->decimal('click_to_call', 20, 2, true);
            $table->unsignedInteger('clicks');
            $table->unsignedInteger('answered');
            $table->unsignedInteger('missed');
            $table->string('date_name');
            $table->dateTime('date_from');
            $table->dateTime('date_to');
            $table->foreignId('client_id');
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
        Schema::dropIfExists('statistics');
    }
}
