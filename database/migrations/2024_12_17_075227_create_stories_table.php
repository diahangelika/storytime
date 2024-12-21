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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('story');
            $table->foreignId('category_id')
                ->nullOnDelete()
                ->cascadeOnUpdate()
                ->constrained('categories');
            $table->foreignId('user_id')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->constrained('users');
            // $table->string('cover_image')->nullable();
            $table->text('images')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book');
    }
};
