<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_selfie_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('attendance_date');
            $table->time('check_in_time');
            $table->string('selfie_path', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_address', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info', 255)->nullable();
            $table->string('type', 20)->default('check_in');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('company_id')->default(1);
            $table->timestamps();
            $table->index(['user_id', 'attendance_date']);
            $table->index(['attendance_date']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_selfie_attendances');
    }
};
