<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix google_map_location: varchar(255) → text
        DB::statement("ALTER TABLE clients ALTER COLUMN google_map_location TYPE TEXT");

        // Fix nearby_landmark: varchar(255) → text  
        DB::statement("ALTER TABLE clients ALTER COLUMN nearby_landmark TYPE TEXT");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE clients ALTER COLUMN google_map_location TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE clients ALTER COLUMN nearby_landmark TYPE VARCHAR(255)");
    }
};
