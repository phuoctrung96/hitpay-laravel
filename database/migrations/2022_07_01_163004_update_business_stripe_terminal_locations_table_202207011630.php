<?php

use App\Enumerations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades;

class UpdateBusinessStripeTerminalLocationsTable202207011630 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Facades\Schema::table('business_stripe_terminal_locations', function (Blueprint $table) {
            $table->string('payment_provider')->after('name')->nullable();
        });

        Facades\DB::table('business_stripe_terminal_locations')->whereNull('payment_provider')->update([
            'payment_provider' => Enumerations\PaymentProvider::STRIPE_SINGAPORE,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Facades\Schema::table('business_stripe_terminal_locations', function (Blueprint $table) {
            $table->dropColumn('payment_provider');
        });
    }
}
