<?php

use App\Enums\ImportType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('import_histories', function (Blueprint $table) {
            $table->enum('import_type', ImportType::cases())->after('file_path');
            $table->timestamp('rolled_back_at')->nullable()->after('completed_at');

            $table->index('import_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_histories', function (Blueprint $table) {
            $table->dropIndex(['import_type']);
            $table->dropColumn(['import_type', 'rolled_back_at']);
        });
    }
};
