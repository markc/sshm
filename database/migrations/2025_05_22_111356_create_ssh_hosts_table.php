<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ssh_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('hostname');
            $table->integer('port')->default(22);
            $table->string('user')->default('root');
            $table->string('identity_file')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssh_hosts');
    }
};
