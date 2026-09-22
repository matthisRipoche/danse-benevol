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
        Schema::create('invitation_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('email')->nullable();
            $table->enum('status', ['pending', 'used', 'revoked'])->default('pending');
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('used_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_codes');
    }
};
