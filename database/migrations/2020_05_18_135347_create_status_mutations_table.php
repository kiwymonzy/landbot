<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusMutationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('status_mutations', function (Blueprint $table) {
            $table->id();
            $table->longText('campaign');
            $table->string('status_old');
            $table->string('status_new');
            $table->dateTime('date_revert');
            $table->foreignId('client_id');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('status_mutations');
    }
}
