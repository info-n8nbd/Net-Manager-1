<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_user_type_check CHECK (user_type::text = ANY (ARRAY['super_admin','admin','employee','client','reseller','technician','sub_reseller','manager','card_distributor','billman']::text[]))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_user_type_check CHECK (user_type::text = ANY (ARRAY['super_admin','admin','employee','client','reseller','technician','sub_reseller','manager','card_distributor']::text[]))");
    }
};
