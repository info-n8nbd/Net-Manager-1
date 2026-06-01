<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE new_connection_applications DROP CONSTRAINT IF EXISTS new_connection_applications_connection_type_check");
        DB::statement("ALTER TABLE new_connection_applications ADD CONSTRAINT new_connection_applications_connection_type_check CHECK (connection_type::text = ANY (ARRAY['home','corporate','PPPoE','Static IP','DHCP','pppoe','static','dhcp']::text[]))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE new_connection_applications DROP CONSTRAINT IF EXISTS new_connection_applications_connection_type_check");
        DB::statement("ALTER TABLE new_connection_applications ADD CONSTRAINT new_connection_applications_connection_type_check CHECK (connection_type::text = ANY (ARRAY['home','corporate']::text[]))");
    }
};
