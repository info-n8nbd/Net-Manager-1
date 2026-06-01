<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('splitters')) {
            Schema::create('splitters', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('1:8');
                $table->foreignId('olt_device_id')->nullable()->constrained('olt_devices')->nullOnDelete();
                $table->foreignId('parent_splitter_id')->nullable()->constrained('splitters')->nullOnDelete();
                $table->integer('port_number')->nullable();
                $table->string('location')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['olt_device_id', 'parent_splitter_id']);
            });
        }

        if (!Schema::hasColumn('clients', 'splitter_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->foreignId('splitter_id')->nullable()->after('olt_id')->constrained('splitters')->nullOnDelete();
                $table->integer('splitter_port')->nullable()->after('splitter_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'splitter_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropConstrainedForeignId('splitter_id');
                $table->dropColumn('splitter_port');
            });
        }
        Schema::dropIfExists('splitters');
    }
};
