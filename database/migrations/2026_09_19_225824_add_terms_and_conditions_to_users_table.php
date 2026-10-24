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
            $table->boolean('is_designated_admin')->default(false)->after('remember_token');
            $table->timestamp('terms_accepted_at')->nullable()->after('is_designated_admin');
            $table->string('terms_ip', 45)->nullable()->after('terms_accepted_at');
            $table->string('terms_version', 20)->default('1.0')->after('terms_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_designated_admin', 'terms_accepted_at', 'terms_ip', 'terms_version']);
        });
    }
};
