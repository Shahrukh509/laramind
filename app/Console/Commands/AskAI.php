<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\AIService;


class AskAI extends Command
{
    protected $signature = 'ai:ask {question}';
    protected $description = 'AI se sawal poochein knowledge base ki bunyaad par';
    
    /**
     * Execute the console command.
     */
     public function handle(AIService $ai)
    {
        $question = $this->argument('question');
        $this->info("Searching for answer...");

        // 1. Sawal ka Embedding (Vector) banayein
        $queryVector = $ai->getEmbedding($question);
        $vectorString = json_encode($queryVector);

        // 2. Vector Search (Cosine Similarity)
        // Hum Postgres ke <=> operator ko use kar ke sab se milti julti row nikalenge
        $context = DB::table('knowledge_base')
            ->select('content')
            ->orderByRaw("embedding <=> ?::vector", [$vectorString])
            ->first();

        if (!$context) {
            $this->error("Mujhe is bare mein kuch nahi pata.");
            return;
        }

        $this->comment("Found Context: " . $context->content);

        // 3. AI ko context de kar jawab mangna
        $systemPrompt = "You are a helpful assistant for LaraMind. Use the following context to answer the user: " . $context->content;
        
        $response = $ai->askChat($systemPrompt, $question);

        $this->info("AI Answer: " . $response->choices[0]->message->content);
    }
}
