<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('claim_records', function (Blueprint $table) {
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
        });

        // Backfill existing records from raw_data JSON
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("
                UPDATE claim_records SET 
                guarantor = COALESCE(raw_data->>'GUARANTOR', ''),
                total_guarantee = COALESCE(CAST(NULLIF(raw_data->>'TOTAL GUARANTEE', '') AS NUMERIC), 0),
                total_payment = COALESCE(CAST(NULLIF(raw_data->>'TOTAL PAYMENT', '') AS NUMERIC), 0),
                total_discount_per_item = COALESCE(CAST(NULLIF(raw_data->>'TOTAL DISCOUNT PER ITEM', '') AS NUMERIC), 0),
                total_invoice_before_discount = COALESCE(CAST(NULLIF(raw_data->>'TOTAL INVOICE BEFORE DISCOUNT', '') AS NUMERIC), 0),
                hospital_guarantee = COALESCE(CAST(NULLIF(raw_data->>'HOSPITAL GUARANTEE', '') AS NUMERIC), 0),
                total_must_be_paid = COALESCE(CAST(NULLIF(raw_data->>'TOTAL MUST BE PAID', '') AS NUMERIC), 0),
                hospital_fee = COALESCE(CAST(NULLIF(raw_data->>'HOSPITAL FEE', '') AS NUMERIC), 0),
                doctor_guarantee = COALESCE(CAST(NULLIF(raw_data->>'DOCTOR GUARANTEE', '') AS NUMERIC), 0),
                doctor_fee = COALESCE(CAST(NULLIF(raw_data->>'DOCTOR FEE', '') AS NUMERIC), 0)
                WHERE raw_data IS NOT NULL
            ");
        } elseif ($driver === 'sqlite') {
            DB::statement("
                UPDATE claim_records SET 
                guarantor = COALESCE(json_extract(raw_data, '$.GUARANTOR'), ''),
                total_guarantee = COALESCE(CAST(json_extract(raw_data, '$.\"TOTAL GUARANTEE\"') AS NUMERIC), 0),
                total_payment = COALESCE(CAST(json_extract(raw_data, '$.\"TOTAL PAYMENT\"') AS NUMERIC), 0),
                total_discount_per_item = COALESCE(CAST(json_extract(raw_data, '$.\"TOTAL DISCOUNT PER ITEM\"') AS NUMERIC), 0),
                total_invoice_before_discount = COALESCE(CAST(json_extract(raw_data, '$.\"TOTAL INVOICE BEFORE DISCOUNT\"') AS NUMERIC), 0),
                hospital_guarantee = COALESCE(CAST(json_extract(raw_data, '$.\"HOSPITAL GUARANTEE\"') AS NUMERIC), 0),
                total_must_be_paid = COALESCE(CAST(json_extract(raw_data, '$.\"TOTAL MUST BE PAID\"') AS NUMERIC), 0),
                hospital_fee = COALESCE(CAST(json_extract(raw_data, '$.\"HOSPITAL FEE\"') AS NUMERIC), 0),
                doctor_guarantee = COALESCE(CAST(json_extract(raw_data, '$.\"DOCTOR GUARANTEE\"') AS NUMERIC), 0),
                doctor_fee = COALESCE(CAST(json_extract(raw_data, '$.\"DOCTOR FEE\"') AS NUMERIC), 0)
                WHERE raw_data IS NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claim_records', function (Blueprint $table) {
            $table->dropColumn([
                'guarantor',
                'total_guarantee',
                'total_payment',
                'total_discount_per_item',
                'total_invoice_before_discount',
                'hospital_guarantee',
                'total_must_be_paid',
                'hospital_fee',
                'doctor_guarantee',
                'doctor_fee',
            ]);
        });
    }
};
