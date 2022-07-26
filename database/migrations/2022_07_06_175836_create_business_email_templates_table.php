<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessEmailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->json('common_customisation');
            $table->json('order_confirmation_template');
            $table->json('payment_receipt_template');
            $table->json('invoice_receipt_template');
            $table->json('mobile_printer_template');
            $table->json('recurring_invoice_template');
            $table->json('action_button_text');
            $table->json('action_button_text_color');
            $table->json('action_button_background_color');
            $table->timestamps();

            $table->index($columns = [
                'business_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_email_templates');
    }
}
