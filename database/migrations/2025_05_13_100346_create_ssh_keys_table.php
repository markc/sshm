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
        Schema::create('ssh_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Key filename (without extension)
            $table->string('comment')->nullable(); // Comment associated with the key
            $table->string('algorithm')->nullable(); // Key algorithm (e.g., ed25519, rsa)
            $table->integer('bits')->nullable(); // Key length in bits
            $table->string('fingerprint')->nullable(); // Fingerprint for identification
            $table->boolean('has_password')->default(false); // Whether the key has a password
            $table->string('path')->nullable(); // Full path to the key file
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssh_keys');
    }
};
