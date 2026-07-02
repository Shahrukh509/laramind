<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIService;
use App\Models\Order;
use OpenAI;

class RunAgent extends Command
{
    protected $signature = 'ai:agent {prompt}';

    public function handle(AIService $ai)
    {
        $userPrompt = $this->argument('prompt');
        $client = OpenAI::client(env('OPENAI_API_KEY'));

        // 1. AI ko prompt bhejna sath mein "Tools" ki list bhi dena
        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $userPrompt]],
            'tools' => $ai->getTools(), // AI ko bataya ke ye tools hain
            'tool_choice' => 'auto',
        ]);

        $message = $response->choices[0]->message;
        $this->info("AI message: ".json_encode($message));

        // 2. Check karna ke kya AI tool use karna chahta hai?

    if ($message->toolCalls) {
    foreach ($message->toolCalls as $toolCall) {
        $functionName = $toolCall->function->name;
        $arguments = json_decode($toolCall->function->arguments, true);

        $this->info("AI is calling function: $functionName");

        if ($functionName === 'get_order_status') {
            $order = Order::where('order_number', $arguments['order_number'])->first();
            $result = $order ? "Status: " . $order->status : "Order not found.";
            
            $this->comment("Database Result: " . $result);
            if ($order && $order->status === 'delayed') {
                $this->warn("Order delayed hai! Triggering n8n workflow...");
                $ai->triggerWorkflow("Customer is asking about a delayed order.", [
                    'order_id' => $order->order_number,
                    'email' => $order->customer_email
                ]);
            }

            // FIX: Assistant message ko array mein convert karein aur content check karein
            $assistantMessage = $message->toArray();
            $assistantMessage['content'] = $assistantMessage['content'] ?? ''; // Agar null ho to empty string kar do

            $finalResponse = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt],
                    $assistantMessage, // Assistant ka pichla message (jis mein tool call thi)
                    [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall->id,
                        'content' => (string) $result, // Result ko string mein cast karein
                    ]
                ]
            ]);

            $this->info("AI Agent: " . $finalResponse->choices[0]->message->content);
        }
    }
}
    }
}