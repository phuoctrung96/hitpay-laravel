<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToBusinessProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_products', function (Blueprint $table) {
            $table->string('status')->after('is_pinned')->nullable();
        });

        DB::table('business_products')->whereNull('published_at')->update([
            'status' => \App\Enumerations\Business\ProductStatus::DRAFT,
        ]);

        DB::table('business_products')->whereNotNull('published_at')->update([
            'status' => \App\Enumerations\Business\ProductStatus::PUBLISHED,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_products', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
