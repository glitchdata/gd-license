<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('license_id')->index();
            $table->string('domain');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_domains');
    }
};
