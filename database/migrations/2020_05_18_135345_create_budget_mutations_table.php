<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetMutationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budget_mutations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('amount_old');
            $table->unsignedInteger('amount_adjust');
            $table->unsignedInteger('amount_new');
            $table->longText('campaign');
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
        Schema::dropIfExists('budget_mutations');
    }
}
