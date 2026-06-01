<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bandwidth_categories')) {
            Schema::create('bandwidth_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->string('key', 50);
                $table->string('label', 100);
                $table->string('icon', 60)->default('fas fa-globe');
                $table->string('color', 30)->default('secondary');
                $table->unsignedTinyInteger('sort_order')->default(99);
                $table->boolean('is_system')->default(false);
                $table->string('mbps_field', 60)->nullable();
                $table->string('rate_field', 60)->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'key']);
            });
        }

        if (!Schema::hasTable('reseller_bw_allocations')) {
            Schema::create('reseller_bw_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('reseller_id');
                $table->unsignedBigInteger('category_id');
                $table->decimal('mbps', 10, 2)->default(0);
                $table->decimal('rate', 10, 2)->default(0);
                $table->timestamps();
                $table->unique(['reseller_id', 'category_id']);
                $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
                $table->foreign('category_id')->references('id')->on('bandwidth_categories')->onDelete('cascade');
            });
        }

        // Seed system categories per company
        $companies = DB::table('companies')->pluck('id');
        $systemCats = [
            ['key'=>'intl',  'label'=>'International (Raw)',                'icon'=>'fas fa-globe',          'color'=>'secondary', 'sort_order'=>1, 'mbps_field'=>'raw_bandwidth',   'rate_field'=>'intl_rate'],
            ['key'=>'ggc',   'label'=>'GGC (Google Global Cache)',           'icon'=>'fab fa-google',         'color'=>'info',      'sort_order'=>2, 'mbps_field'=>'ggc_bandwidth',   'rate_field'=>'ggc_rate'],
            ['key'=>'fna',   'label'=>'FNA (Facebook Network Appliance)',    'icon'=>'fab fa-facebook',       'color'=>'primary',   'sort_order'=>3, 'mbps_field'=>'fna_bandwidth',   'rate_field'=>'fna_rate'],
            ['key'=>'cdn',   'label'=>'CDN (Content Delivery Network)',      'icon'=>'fas fa-cloud',          'color'=>'purple',    'sort_order'=>4, 'mbps_field'=>'cdn_bandwidth',   'rate_field'=>'cdn_rate'],
            ['key'=>'bdix',  'label'=>'BDIX (Bangladesh Internet Exchange)', 'icon'=>'fas fa-network-wired',  'color'=>'success',   'sort_order'=>5, 'mbps_field'=>'bdix_bandwidth',  'rate_field'=>'bdix_rate'],
            ['key'=>'cache', 'label'=>'Private Cache / Mirror',              'icon'=>'fas fa-hdd',            'color'=>'warning',   'sort_order'=>6, 'mbps_field'=>'cache_bandwidth', 'rate_field'=>'cache_rate'],
        ];
        $now = now();
        foreach ($companies as $companyId) {
            foreach ($systemCats as $cat) {
                $exists = DB::table('bandwidth_categories')
                    ->where('company_id', $companyId)->where('key', $cat['key'])->exists();
                if (!$exists) {
                    DB::table('bandwidth_categories')->insert(array_merge($cat, [
                        'company_id' => $companyId,
                        'is_system'  => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_bw_allocations');
        Schema::dropIfExists('bandwidth_categories');
    }
};
