<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Support chat on the existing chat_messages table. A support
     * conversation is one thread per user with the admin, so it needs
     * no offer and no member: the rows are keyed by support_user_id
     * instead, with offer_id and member_id left null. No second chat
     * system is built: the same table, the same plaintext body rule
     * (see the ChatMessage model) and the same flag columns carry the
     * support messages, and the sender_id tells a user message from
     * an admin reply.
     */
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->foreignId('support_user_id')->nullable()->after('member_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->change();
            $table->foreignId('member_id')->nullable()->change();
            $table->index('support_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropIndex(['support_user_id']);
            $table->dropConstrainedForeignId('support_user_id');
            $table->foreignId('offer_id')->change();
            $table->foreignId('member_id')->change();
        });
    }
};
