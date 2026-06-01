<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnsToAdd = [
            'father_husband_name' => fn($table) => $table->string('father_husband_name')->nullable(),
            'date_of_birth' => fn($table) => $table->date('date_of_birth')->nullable(),
            'nearby_landmark' => fn($table) => $table->string('nearby_landmark')->nullable(),
            'billing_cycle' => fn($table) => $table->string('billing_cycle')->default('monthly'),
            'installation_charge' => fn($table) => $table->decimal('installation_charge', 12, 2)->default(0),
            'cable_length_meters' => fn($table) => $table->decimal('cable_length_meters', 10, 2)->default(0),
            'onu_required' => fn($table) => $table->boolean('onu_required')->default(false),
            'router_required' => fn($table) => $table->boolean('router_required')->default(false),
            'preferred_payment_method' => fn($table) => $table->string('preferred_payment_method')->nullable(),
            'technician_note' => fn($table) => $table->text('technician_note')->nullable(),
            'application_id' => fn($table) => $table->unsignedBigInteger('application_id')->nullable(),
        ];

        foreach ($columnsToAdd as $column => $callback) {
            if (!Schema::hasColumn('clients', $column)) {
                Schema::table('clients', function (Blueprint $table) use ($callback) {
                    $callback($table);
                });
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'father_husband_name', 'date_of_birth', 'nearby_landmark', 
            'billing_cycle', 'installation_charge', 'cable_length_meters',
            'onu_required', 'router_required', 'preferred_payment_method',
            'technician_note', 'application_id'
        ];
        
        foreach ($columns as $column) {
            if (Schema::hasColumn('clients', $column)) {
                Schema::table('clients', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
