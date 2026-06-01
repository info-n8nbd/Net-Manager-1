<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'page_features')) {
                $table->json('page_features')->nullable()->after('allowed_menus');
            }
        });
    }
    public function down(): void {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumnIfExists('page_features');
        });
    }
};
