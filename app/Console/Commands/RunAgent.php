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

        // 1. give prompts to ai and also list of tools
        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $userPrompt]],
            'tools' => $ai->getTools(), // to highlight tools with ai
            'tool_choice' => 'auto',
        ]);

        $message = $response->choices[0]->message;
        $this->info("AI message: ".json_encode($message));

        // 2. Check If ai want to use any tool?

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
        }elseif ($functionName === 'get_user_status') {
    $this->info("AI is asking for user info...");
    
    // Asal mein ye database (Auth::user()) se aata, lekin hum abhi hardcode kar rahe hain
    $result = "User Name: Shahrukh, Role: Senior Developer"; 
    
    $this->comment("System Result: " . $result);

    // AI ko result wapis bhejna (Wahi logic jo pehle thi)
    $assistantMessage = $message->toArray();
    $assistantMessage['content'] = $assistantMessage['content'] ?? '';

    $finalResponse = $client->chat()->create([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => $userPrompt],
            $assistantMessage,
            [
                'role' => 'tool',
                'tool_call_id' => $toolCall->id,
                'content' => (string) $result,
            ]
        ]
    ]);

    $this->info("AI Agent: " . $finalResponse->choices[0]->message->content);
}
    }
}
    }
}