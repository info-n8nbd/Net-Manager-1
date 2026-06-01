<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            if (!Schema::hasColumn('resellers', 'intl_rate')) {
                $table->decimal('intl_rate', 10, 2)->nullable()->after('bandwidth_monthly_rate')
                    ->comment('Rate per Mbps for International/Raw bandwidth');
            }
            if (!Schema::hasColumn('resellers', 'ggc_bandwidth')) {
                $table->decimal('ggc_bandwidth', 10, 2)->nullable()->after('intl_rate');
            }
            if (!Schema::hasColumn('resellers', 'ggc_rate')) {
                $table->decimal('ggc_rate', 10, 2)->nullable()->after('ggc_bandwidth');
            }
            if (!Schema::hasColumn('resellers', 'fna_bandwidth')) {
                $table->decimal('fna_bandwidth', 10, 2)->nullable()->after('ggc_rate');
            }
            if (!Schema::hasColumn('resellers', 'fna_rate')) {
                $table->decimal('fna_rate', 10, 2)->nullable()->after('fna_bandwidth');
            }
            if (!Schema::hasColumn('resellers', 'cdn_bandwidth')) {
                $table->decimal('cdn_bandwidth', 10, 2)->nullable()->after('fna_rate');
            }
            if (!Schema::hasColumn('resellers', 'cdn_rate')) {
                $table->decimal('cdn_rate', 10, 2)->nullable()->after('cdn_bandwidth');
            }
            if (!Schema::hasColumn('resellers', 'bdix_bandwidth')) {
                $table->decimal('bdix_bandwidth', 10, 2)->nullable()->after('cdn_rate');
            }
            if (!Schema::hasColumn('resellers', 'bdix_rate')) {
                $table->decimal('bdix_rate', 10, 2)->nullable()->after('bdix_bandwidth');
            }
            if (!Schema::hasColumn('resellers', 'cache_bandwidth')) {
                $table->decimal('cache_bandwidth', 10, 2)->nullable()->after('bdix_rate');
            }
            if (!Schema::hasColumn('resellers', 'cache_rate')) {
                $table->decimal('cache_rate', 10, 2)->nullable()->after('cache_bandwidth');
            }
        });

        Schema::table('reseller_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('reseller_bills', 'bandwidth_breakdown')) {
                $table->json('bandwidth_breakdown')->nullable()->after('closing_balance')
                    ->comment('Per-category bandwidth breakdown at time of billing');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $cols = ['intl_rate','ggc_bandwidth','ggc_rate','fna_bandwidth','fna_rate',
                     'cdn_bandwidth','cdn_rate','bdix_bandwidth','bdix_rate','cache_bandwidth','cache_rate'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('resellers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('reseller_bills', function (Blueprint $table) {
            if (Schema::hasColumn('reseller_bills', 'bandwidth_breakdown')) {
                $table->dropColumn('bandwidth_breakdown');
            }
        });
    }
};
