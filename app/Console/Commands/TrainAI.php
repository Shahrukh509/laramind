<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIService;
use Illuminate\Support\Facades\DB;

class TrainAI extends Command
{
    // Ye line check karein, isi se "ai:train" banta hai
    protected $signature = 'ai:train {text} {--source=manual}'; 

    protected $description = 'Text ko vector mein badal kar DB mein save karein';

    public function handle(AIService $ai)
    {
        $text = $this->argument('text');
        $this->info("AI ko sikhaya ja raha hai: " . $text);

        try {
            $vector = $ai->getEmbedding($text);

            DB::table('knowledge_base')->insert([
                'content' => $text,
                'source' => $this->option('source'),
                'embedding' => json_encode($vector),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("Success: Knowledge Base update ho gayi!");
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}