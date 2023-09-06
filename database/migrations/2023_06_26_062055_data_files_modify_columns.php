<?php

use App\Models\DataFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DataFile::truncate();
        Schema::table('data_files', function (Blueprint $table) {
            $table->renameColumn('path', 'file_name');
            $table->renameColumn('size', 'file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
