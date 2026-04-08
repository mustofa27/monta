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
        Schema::table('ta_supervisions', function (Blueprint $table) {
            $table->foreignId('ta_document_id')
                ->nullable()
                ->after('summary')
                ->constrained('ta_documents')
                ->nullOnDelete();

            $table->index('ta_document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ta_supervisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ta_document_id');
        });
    }
};
