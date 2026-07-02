<?php
namespace App\Services;

use Http;
use OpenAI;

class AIService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }

    /**
     * Text ko Vector (numbers) mein convert karna
     */
    public function getEmbedding(string $text): array
    {
        $response = $this->client->embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
    }

    /**
     * AI se jawab mangna (Chat)
     */
    public function askChat(string $systemPrompt, string $userPrompt)
    {
        return $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);
    }

    public function getTools()
{
    return [
        [
            "type" => "function",
            "function" => [
                "name" => "get_order_status",
                "description" => "Get the current status of an order using the order number",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "order_number" => [
                            "type" => "string",
                            "description" => "The order number, e.g. ORD-123",
                        ],
                    ],
                    "required" => ["order_number"],
                ],
            ],
            [
                "name" => "get_name",
                "description" => "tell the name shahrukh",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "name" => [
                            "type" => "string",
                            "description" => "Your Name is Shahrukh",
                        ],
                    ],
                    "required" => ["name"],
                ],
            ],
        ]
    ];
}
public function triggerWorkflow(string $reason, array $data)
{
    // n8n ka URL (Docker network mein service ka naam 'n8n' hai)
    $url = "http://localhost:5678/webhook-test/order-alert"; 

    return Http::post($url, [
        'event' => 'order_issue_detected',
        'reason' => $reason,
        'details' => $data,
        'timestamp' => now()->toDateTimeString()
    ]);
}
}