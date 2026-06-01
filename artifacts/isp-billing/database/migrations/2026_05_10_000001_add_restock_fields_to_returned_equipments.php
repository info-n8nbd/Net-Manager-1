<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table("returned_equipments", function (Blueprint $table) {
            $table->boolean("is_restocked")->default(false)->after("company_id");
            $table->timestamp("restocked_at")->nullable()->after("is_restocked");
            $table->unsignedBigInteger("restocked_by")->nullable()->after("restocked_at");
            $table->text("restock_notes")->nullable()->after("restocked_by");
            $table->string("restock_type", 30)->nullable()->after("restock_notes");
            $table->unsignedBigInteger("restock_ref_id")->nullable()->after("restock_type");
            $table->unsignedBigInteger("fiber_stock_id")->nullable()->after("restock_ref_id");
            $table->decimal("fiber_meter", 10, 2)->nullable()->after("fiber_stock_id");
            $table->unsignedBigInteger("assigned_to_client_id")->nullable()->after("fiber_meter");
            $table->timestamp("assigned_at")->nullable()->after("assigned_to_client_id");
        });
    }
    public function down(): void {
        Schema::table("returned_equipments", function (Blueprint $table) {
            $table->dropColumn(["is_restocked","restocked_at","restocked_by","restock_notes","restock_type","restock_ref_id","fiber_stock_id","fiber_meter","assigned_to_client_id","assigned_at"]);
        });
    }
};
