<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("olt_pon_port_metrics", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("olt_device_id");
            $table->string("pon_port", 30);
            $table->float("tx_power")->nullable();
            $table->float("temperature")->nullable();
            $table->float("voltage")->nullable();
            $table->float("tx_bias")->nullable();
            $table->timestamp("polled_at")->nullable();
            $table->timestamps();

            $table->unique(["olt_device_id", "pon_port"]);
            $table->foreign("olt_device_id")->references("id")->on("olt_devices")->onDelete("cascade");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("olt_pon_port_metrics");
    }
};
