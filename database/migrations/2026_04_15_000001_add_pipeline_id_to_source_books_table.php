<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add pipeline_id to source_books.
     *
     * This column is the routing key that tells PrefectService which Python
     * deployment to trigger when "Start Ingestion" is clicked in Filament.
     * The value must match the flow name registered in serve_pipeline.py.
     */
    public function up(): void
    {
        Schema::table('source_books', function (Blueprint $table) {
            $table->string('pipeline_id')
                ->default('standard_txt_flow')
                ->after('resource_type')
                ->comment('Prefect deployment name — routes to the correct AI pipeline (e.g. quran_api_flow, standard_txt_flow)');
        });
    }

    public function down(): void
    {
        Schema::table('source_books', function (Blueprint $table) {
            $table->dropColumn('pipeline_id');
        });
    }
};
