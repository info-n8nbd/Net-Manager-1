<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        $indexes = [
            'idx_clients_name_trgm' => 'CREATE INDEX IF NOT EXISTS idx_clients_name_trgm ON clients USING gin (name gin_trgm_ops)',
            'idx_clients_client_id_trgm' => 'CREATE INDEX IF NOT EXISTS idx_clients_client_id_trgm ON clients USING gin (client_id gin_trgm_ops)',
            'idx_clients_phone_trgm' => 'CREATE INDEX IF NOT EXISTS idx_clients_phone_trgm ON clients USING gin (phone gin_trgm_ops)',
            'idx_clients_username_trgm' => 'CREATE INDEX IF NOT EXISTS idx_clients_username_trgm ON clients USING gin (username gin_trgm_ops)',
            'idx_clients_mac_trgm' => 'CREATE INDEX IF NOT EXISTS idx_clients_mac_trgm ON clients USING gin (mac_address gin_trgm_ops)',
            'idx_clients_ip_trgm' => 'CREATE INDEX IF NOT EXISTS idx_clients_ip_trgm ON clients USING gin (ip_address gin_trgm_ops)',
            'idx_clients_email_trgm' => 'CREATE INDEX IF NOT EXISTS idx_clients_email_trgm ON clients USING gin (email gin_trgm_ops)',
            'idx_clients_nid_trgm' => 'CREATE INDEX IF NOT EXISTS idx_clients_nid_trgm ON clients USING gin (nid gin_trgm_ops)',
            'idx_clients_package_id' => 'CREATE INDEX IF NOT EXISTS idx_clients_package_id ON clients USING btree (package_id)',
            'idx_clients_mikrotik_device_id' => 'CREATE INDEX IF NOT EXISTS idx_clients_mikrotik_device_id ON clients USING btree (mikrotik_device_id)',
            'idx_clients_billing_date' => 'CREATE INDEX IF NOT EXISTS idx_clients_billing_date ON clients USING btree (billing_date)',
            'idx_clients_due_amount' => 'CREATE INDEX IF NOT EXISTS idx_clients_due_amount ON clients USING btree (due_amount)',
            'idx_clients_connection_type' => 'CREATE INDEX IF NOT EXISTS idx_clients_connection_type ON clients USING btree (connection_type)',
            'idx_clients_expire_date' => 'CREATE INDEX IF NOT EXISTS idx_clients_expire_date ON clients USING btree (expire_date)',
            'idx_clients_created_at' => 'CREATE INDEX IF NOT EXISTS idx_clients_created_at ON clients USING btree (created_at DESC)',
        ];

        foreach ($indexes as $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        $indexes = [
            'idx_clients_name_trgm', 'idx_clients_client_id_trgm', 'idx_clients_phone_trgm',
            'idx_clients_username_trgm', 'idx_clients_mac_trgm', 'idx_clients_ip_trgm',
            'idx_clients_email_trgm', 'idx_clients_nid_trgm', 'idx_clients_package_id',
            'idx_clients_mikrotik_device_id', 'idx_clients_billing_date', 'idx_clients_due_amount',
            'idx_clients_connection_type', 'idx_clients_expire_date', 'idx_clients_created_at',
        ];

        foreach ($indexes as $idx) {
            DB::statement("DROP INDEX IF EXISTS {$idx}");
        }
    }
};
