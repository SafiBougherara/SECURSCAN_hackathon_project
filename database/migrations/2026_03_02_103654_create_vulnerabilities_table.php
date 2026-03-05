<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vulnerabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('tool');
            $table->string('check_id')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('line_start')->nullable();
            $table->enum('severity', ['critical', 'high', 'medium', 'low', 'info'])->default('info');
            $table->text('message');
            $table->string('owasp_category')->nullable();
            $table->string('owasp_label')->nullable();
            $table->text('fix_suggestion')->nullable();
            $table->boolean('selected')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vulnerabilities');
    }
};
