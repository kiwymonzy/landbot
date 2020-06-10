<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetRecommendationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budget_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('campaign');
            $table->unsignedInteger('budget');
            $table->unsignedInteger('calls');
            $table->unsignedDouble('change');
            $table->unsignedTinyInteger('status')->default(1);
            $table->string('account_id');
            $table->string('budget_id');
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
        Schema::dropIfExists('budget_recommendations');
    }
}
