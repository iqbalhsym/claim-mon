<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_records', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm')->index()->nullable();
            $table->string('nama_pasien')->nullable();
            $table->date('registered_date')->nullable();
            $table->date('discharge_date')->nullable();
            $table->string('jenis_rawat', 10)->index()->nullable(); // ranap or rajal
            
            // Guarantor & financial columns
            $table->string('guarantor')->nullable();
            $table->decimal('total_guarantee', 15, 2)->default(0);
            $table->decimal('total_payment', 15, 2)->default(0);
            $table->decimal('total_discount_per_item', 15, 2)->default(0);
            $table->decimal('total_invoice_before_discount', 15, 2)->default(0);
            $table->decimal('hospital_guarantee', 15, 2)->default(0);
            $table->decimal('total_must_be_paid', 15, 2)->default(0);
            $table->decimal('hospital_fee', 15, 2)->default(0);
            $table->decimal('doctor_guarantee', 15, 2)->default(0);
            $table->decimal('doctor_fee', 15, 2)->default(0);

            // Keep raw_data for future flexibility
            $table->json('raw_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_records');
    }
};
