<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Memberships for shared subscriptions. One row per co-subscriber
     * (plus the owner as a member) of an offer. The row id is the member
     * id used in chat and detail URLs. Username and initials are
     * snapshots: they stay readable even if the linked user record is
     * removed, which is why user_id is nullable.
     */
    public function up(): void
    {
        Schema::create('offer_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username');
            $table->string('initials', 2);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('status')->default('current');
            $table->string('invitation_email')->nullable();
            $table->timestamp('invite_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_members');
    }
};
