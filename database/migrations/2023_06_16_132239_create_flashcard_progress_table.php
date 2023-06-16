<?php

use App\Models\Flashcard;
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
        Schema::create('flashcard_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Flashcard::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('username')->index();
            $table->tinyInteger('status');
            $table->timestamps();

            $table->unique(['flashcard_id', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcard_progress');
    }
};
