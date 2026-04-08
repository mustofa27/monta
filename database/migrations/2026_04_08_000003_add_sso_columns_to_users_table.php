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
        Schema::table('users', function (Blueprint $table) {
            $table->string('sso_sub')->nullable()->unique()->after('email');
            $table->string('sso_user_type')->nullable()->after('remember_token');
            $table->string('sso_employee_type')->nullable()->after('sso_user_type');
            $table->json('sso_profile')->nullable()->after('sso_employee_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sso_profile', 'sso_employee_type', 'sso_user_type']);
            $table->dropUnique('users_sso_sub_unique');
            $table->dropColumn('sso_sub');
        });
    }
};
