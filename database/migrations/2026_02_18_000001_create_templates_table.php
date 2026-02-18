<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('key', 120)->unique();
            $table->string('name', 150);
            $table->enum('channel', ['email', 'whatsapp', 'push'])->default('email');
            $table->string('subject', 190)->nullable();
            $table->longText('body');
            $table->json('variables_schema')->default('{}');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
