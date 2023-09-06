<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('domain');
            $table->boolean('is_active')->default(true);
            $table->uuid('team_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain');
    }
};
