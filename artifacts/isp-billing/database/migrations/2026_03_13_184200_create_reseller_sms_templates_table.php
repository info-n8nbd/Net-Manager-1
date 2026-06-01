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
        Schema::create('reseller_sms_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_id');
            $table->string('name');
            $table->string('type')->default('general');
            $table->text('content');
            $table->timestamps();

            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
            $table->index('reseller_id');
        });

        if (!Schema::hasColumn('reseller_sms_settings', 'auto_process_settings')) {
            Schema::table('reseller_sms_settings', function (Blueprint $table) {
                $table->json('auto_process_settings')->nullable()->after('event_settings');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_sms_templates');
        if (Schema::hasColumn('reseller_sms_settings', 'auto_process_settings')) {
            Schema::table('reseller_sms_settings', function (Blueprint $table) {
                $table->dropColumn('auto_process_settings');
            });
        }
    }
};
