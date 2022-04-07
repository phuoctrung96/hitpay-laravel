<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Business;

class CreateBusinessComplianceNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_compliance_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->index();
            $table->string('risk_level')->nullable();
            $table->string('compliance_notes')->nullable();
            $table->timestamp('risk_expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');
        });

        Business::query()->each(function (Business $business) {
            if(!$business->complianceNotes && $business->verifications()->first()) {
                $business->complianceNotes()->create([
                    'risk_level' => $business->verifications()->first()->type === 'personal' ? \App\Enumerations\Business\ComplianceRiskLevel::HIGH_RISK : \App\Enumerations\Business\ComplianceRiskLevel::LOW_RISK,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_compliance_notes');
    }
}
