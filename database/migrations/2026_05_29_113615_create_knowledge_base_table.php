<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->id();
        $table->text('content'); // Asal maloomat (e.g. "Humaray office ki timings 9 se 5 hain")
        $table->string('source')->nullable(); // Reference (e.g. "hr_manual.pdf")
        $table->vector('embedding', 1536); // Ye OpenAI ke vectors store karega
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_base');
    }
};
