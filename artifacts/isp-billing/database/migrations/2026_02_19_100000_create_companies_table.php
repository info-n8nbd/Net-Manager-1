<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('alt_phone')->nullable();
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_light')->nullable();
            $table->string('favicon')->nullable();
            $table->string('slogan')->nullable();
            $table->string('website')->nullable();
            $table->string('currency_symbol')->default('৳');
            $table->string('currency_code')->default('BDT');
            $table->string('btrc_license')->nullable();
            $table->string('bin_no')->nullable();
            $table->string('trade_license')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->string('sidebar_color')->default('#1e3c72');
            $table->string('primary_color')->default('#007bff');
            $table->string('ticker_label')->nullable();
            $table->text('ticker_text')->nullable();
            $table->text('login_animation_words')->nullable();
            $table->integer('login_animation_opacity')->default(40);
            $table->integer('max_clients')->default(0);
            $table->json('enabled_features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('subscription_start')->nullable();
            $table->date('subscription_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
