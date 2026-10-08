<?php

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
        Schema::table('trade_requests', function (Blueprint $table) {
            $table->foreignId('offered_book_id')->nullable()->after('book_id')->constrained('books')->nullOnDelete();
        });

        Schema::table('trade_messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->index(['trade_request_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trade_messages', function (Blueprint $table) {
            $table->dropIndex(['trade_request_id', 'read_at']);
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime', 'read_at']);
        });

        Schema::table('trade_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offered_book_id');
        });
    }
};
