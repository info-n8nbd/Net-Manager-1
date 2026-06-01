<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returned_equipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_client_id', 50)->nullable();
            $table->string('equipment_type', 50);
            $table->string('equipment_name');
            $table->string('serial_number', 100)->nullable();
            $table->string('mac_address', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('pon_port', 50)->nullable();
            $table->string('onu_index', 50)->nullable();
            $table->string('condition', 20)->default('unknown');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->date('return_date');
            $table->unsignedBigInteger('company_id')->default(1);
            $table->timestamps();
            $table->index(['client_id']);
            $table->index(['equipment_type']);
            $table->index(['return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returned_equipments');
    }
};
