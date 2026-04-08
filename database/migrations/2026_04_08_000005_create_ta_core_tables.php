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
        Schema::create('ta_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->string('study_program')->nullable();
            $table->string('semester_code', 20)->nullable();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'revision_required', 'completed'])
                ->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'semester_code']);
            $table->index(['student_user_id', 'status']);
        });

        Schema::create('ta_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ta_project_id')->constrained('ta_projects')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->unsignedInteger('weight')->default(0);
            $table->date('due_date')->nullable();
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'approved', 'rejected', 'revision_required'])
                ->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['ta_project_id', 'code']);
            $table->index(['status', 'due_date']);
        });

        Schema::create('ta_supervisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ta_project_id')->constrained('ta_projects')->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('meeting_date');
            $table->text('summary');
            $table->enum('status', ['submitted', 'accepted', 'revision_required'])->default('submitted');
            $table->text('supervisor_note')->nullable();
            $table->timestamps();

            $table->index(['ta_project_id', 'meeting_date']);
            $table->index(['status', 'meeting_date']);
        });

        Schema::create('ta_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ta_project_id')->constrained('ta_projects')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type', 80);
            $table->string('original_name');
            $table->string('stored_path');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->enum('status', ['uploaded', 'under_review', 'approved', 'rejected', 'revision_required'])
                ->default('uploaded');
            $table->timestamps();

            $table->index(['ta_project_id', 'document_type']);
            $table->index(['status', 'document_type']);
        });

        Schema::create('ta_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ta_project_id')->constrained('ta_projects')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ta_document_id')->nullable()->constrained('ta_documents')->nullOnDelete();
            $table->enum('decision', ['approved', 'rejected', 'revision_required']);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['ta_project_id', 'reviewer_user_id']);
        });

        Schema::create('ta_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ta_project_id')->constrained('ta_projects')->cascadeOnDelete();
            $table->enum('schedule_type', ['seminar_proposal', 'sidang_akhir']);
            $table->dateTime('scheduled_at');
            $table->string('room')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();

            $table->index(['schedule_type', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('ta_milestone_templates', function (Blueprint $table) {
            $table->id();
            $table->string('semester_code', 20);
            $table->string('code', 60);
            $table->string('name');
            $table->unsignedInteger('weight')->default(0);
            $table->unsignedInteger('order_no')->default(0);
            $table->timestamps();

            $table->unique(['semester_code', 'code']);
        });

        Schema::create('ta_status_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 50);
            $table->string('code', 60);
            $table->string('label');
            $table->unsignedInteger('order_no')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['domain', 'code']);
            $table->index(['domain', 'order_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ta_status_catalogs');
        Schema::dropIfExists('ta_milestone_templates');
        Schema::dropIfExists('ta_schedules');
        Schema::dropIfExists('ta_reviews');
        Schema::dropIfExists('ta_documents');
        Schema::dropIfExists('ta_supervisions');
        Schema::dropIfExists('ta_milestones');
        Schema::dropIfExists('ta_projects');
    }
};
