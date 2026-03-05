<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vulnerabilities', function (Blueprint $table) {
            $table->longText('ai_fix')->nullable()->after('fix_suggestion');
        });
    }

    public function down(): void
    {
        Schema::table('vulnerabilities', function (Blueprint $table) {
            $table->dropColumn('ai_fix');
        });
    }
};
