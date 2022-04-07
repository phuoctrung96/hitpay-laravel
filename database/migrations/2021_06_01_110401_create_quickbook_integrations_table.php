<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuickbookIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quickbook_integrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('business_id');
            $table->string('access_token', 1024);
            $table->string('refresh_token', 1024);
            $table->string('realm_id', 1024);
            $table->string('sales_account_id')->nullable();
            $table->string('refund_account_id')->nullable();
            $table->string('fee_account_id')->nullable();
            $table->boolean('sales_synchronization_enabled')->nullable();
            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
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
        Schema::dropIfExists('quickbook_integrations');
    }
}
