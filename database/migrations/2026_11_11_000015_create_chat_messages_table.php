<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chat messages. One row per message in a per-member thread: the
     * thread is (offer_id, member_id), matching the messages URL shape
     * /en/my-shared-subscriptions/{hash}/messages/latest/{memberId}.
     *
     * The body column stores PLAINTEXT in v1. The latest-messages API
     * shape names the field `crypted`, but v1 stores plaintext and the
     * endpoint (built in Phase 2) will return the body there. Real
     * encryption is an open follow-up needing a key decision, so no
     * encryption is invented here and no key material is stored.
     * The flag columns mirror the API shape: new, read, notified,
     * is_moderated, waiting_moderation, admin_override. is_mine is not
     * stored; it is derived from sender vs the logged-in user.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('offer_members')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            // Plaintext in v1, see the class comment above.
            $table->text('body');
            $table->boolean('is_new')->default(true);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_notified')->default(false);
            $table->boolean('is_moderated')->default(false);
            $table->tinyInteger('waiting_moderation')->default(0);
            $table->boolean('admin_override')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
