<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bandwidth_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_id');
            $table->string('category_key', 20); // intl, ggc, fna, cdn, bdix, cache
            $table->date('effective_date');
            $table->decimal('old_mbps', 10, 2)->default(0);
            $table->decimal('new_mbps', 10, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
            $table->index(['reseller_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bandwidth_adjustments');
    }
};