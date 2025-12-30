<?php

use App\Enums\ReportStatus;
use App\Enums\ReportType;
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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default(ReportType::User->value);
            $table->unsignedBigInteger('entity_id');
            $table->text('reason');
            $table->string('status')->default(ReportStatus::Open->value);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'entity_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
