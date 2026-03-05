<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_scan_id')->nullable()->after('user_id');
            $table->foreign('parent_scan_id')->references('id')->on('scans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['parent_scan_id']);
            $table->dropColumn('parent_scan_id');
        });
    }
};
