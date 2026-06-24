<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Detect user intent using Gemini Multimodal AI
     * 
     * @param string|null $text
     * @param string|null $mediaBase64
     * @param string|null $mediaMime
     * @param array $recentTransactions
     * @param array $categories
     * @return array
     */
    public function detectIntent($text = null, $mediaBase64 = null, $mediaMime = null, $recentTransactions = [], $categories = [])
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API Key is not set.');
            return ['action' => 'general_chat', 'parameters' => [], 'message' => 'API Key Gemini belum diset di server.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $today = now()->format('Y-m-d');
        $categoriesList = json_encode($categories);
        $transactionsList = json_encode($recentTransactions);

        $systemInstruction = "You are a smart financial AI assistant for the Catat-in app. "
            . "Your job is to parse the user's message (which can be a casual text, image of a receipt/nota, or voice note/audio) and detect their intent. "
            . "Today's date is {$today}.\n\n"
            . "CONTEXT DATA:\n"
            . "- Available categories: {$categoriesList}\n"
            . "- Recent transactions: {$transactionsList}\n\n"
            . "You must output a JSON object matching this schema. DO NOT wrap the output in markdown code blocks. Output raw JSON only.\n"
            . "{\n"
            . "  \"action\": \"create_transaction\" | \"update_transaction\" | \"delete_transaction\" | \"create_category\" | \"delete_category\" | \"get_report\" | \"get_history\" | \"general_chat\",\n"
            . "  \"parameters\": { ... },\n"
            . "  \"message\": \"friendly response or confirmation question in Indonesian\"\n"
            . "}\n\n"
            . "ACTIONS DETAILS:\n"
            . "1. \"create_transaction\": User wants to record income/expense. Parameters:\n"
            . "   - \"type\": \"pemasukan\" or \"pengeluaran\".\n"
            . "   - \"amount\": number.\n"
            . "   - \"category\": string matching one of the available categories (map intelligently). If it doesn't match any, map to the closest one.\n"
            . "   - \"desc\": short description (e.g. \"beli bakso\").\n"
            . "   - \"date\": \"YYYY-MM-DD\" (calculate relative to {$today}).\n"
            . "2. \"update_transaction\": User wants to edit an existing transaction. Parameters:\n"
            . "   - \"transaction_id\": number (find the matching transaction from Recent transactions context. E.g. \"ubah nasi goreng tadi siang\" -> find the transaction with desc containing \"nasi goreng\" from today and extract its ID).\n"
            . "   - \"amount\": new amount if changed (number or null).\n"
            . "   - \"desc\": new description if changed (string or null).\n"
            . "   - \"category\": new category if changed (string or null).\n"
            . "   - \"date\": new date if changed (\"YYYY-MM-DD\" or null).\n"
            . "3. \"delete_transaction\": User wants to delete a transaction. Parameters:\n"
            . "   - \"transaction_id\": number (find the matching transaction from Recent transactions context).\n"
            . "4. \"create_category\": User wants to add a new category. Parameters:\n"
            . "   - \"name\": string.\n"
            . "   - \"type\": \"pemasukan\" or \"pengeluaran\".\n"
            . "5. \"delete_category\": User wants to delete a category. Parameters:\n"
            . "   - \"category_id\": number (match the category name from available categories context to get the ID).\n"
            . "6. \"get_report\": User wants a summary of spending/income. Parameters:\n"
            . "   - \"period\": \"this_month\", \"last_month\", \"this_week\", \"today\", \"yesterday\", or a specific date/month.\n"
            . "   - \"category\": string matching category name (optional, if they filter by category like \"Berapa belanja dapur bulan ini?\").\n"
            . "7. \"get_history\": User wants to see history of transactions. Parameters:\n"
            . "   - \"limit\": number (default 5 or 10).\n"
            . "   - \"period\": \"today\", \"yesterday\", \"this_week\", or null.\n"
            . "8. \"general_chat\": No transaction action detected. The \"message\" key must contain your reply in Indonesian.\n\n"
            . "Rules:\n"
            . "- When confirming database-modifying actions (create, update, delete transaction/category), draft a polite confirmation question in the \"message\" parameter asking the user if they wish to proceed.\n"
            . "- If a transaction cannot be found for update/delete, set action to \"general_chat\" and reply in \"message\" explaining that the transaction wasn't found.";

        $contents = [];
        $parts = [];

        if ($mediaBase64 && $mediaMime) {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $mediaMime,
                    'data' => $mediaBase64
                ]
            ];
        }

        $prompt = "Analyze this input: ";
        if ($text) {
            $prompt .= "\"{$text}\"";
        } else {
            $prompt .= "[Attached Media]";
        }
        $parts[] = ['text' => $prompt];

        $contents[] = ['parts' => $parts];

        $payload = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.1
            ]
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url, $payload);
            if ($response->successful()) {
                $data = $response->json();
                $textResult = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                return json_decode(trim($textResult), true) ?: ['action' => 'general_chat', 'parameters' => [], 'message' => 'Gagal membaca format data AI.'];
            }
            Log::error('Gemini API Error in detectIntent: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Gemini Exception in detectIntent: ' . $e->getMessage());
        }

        return ['action' => 'general_chat', 'parameters' => [], 'message' => 'Terjadi kesalahan saat memproses data dengan AI.'];
    }

    /**
     * Generate a natural response using data context (for reports & history)
     * 
     * @param string $userInput
     * @param array $dataContext
     * @return string
     */
    public function generateResponseWithData($userInput, $dataContext)
    {
        if (empty($this->apiKey)) {
            return 'API Key Gemini belum diatur di server.';
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        
        $contextString = json_encode($dataContext);
        $systemInstruction = "You are a smart financial AI assistant for the Catat-in app. "
            . "The user has asked a query about their finances, and the database returned this data: {$contextString}.\n\n"
            . "Your job is to compose a helpful, natural, and friendly response in Indonesian summarizing this data. "
            . "If it's a report/summary, mention the total and summarize key points or largest transactions. "
            . "If it's transaction history, list them in a neat bulleted format (e.g. using emojis) that is easy to read on WhatsApp. "
            . "Keep the response concise and friendly.";

        $payload = [
            'contents' => [
                ['parts' => [['text' => "User query: \"{$userInput}\""]]]
            ],
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'generationConfig' => [
                'temperature' => 0.7
            ]
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url, $payload);
            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Gagal membuat tanggapan AI.';
            }
            Log::error('Gemini API Error in generateResponse: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Gemini Exception in generateResponse: ' . $e->getMessage());
        }

        return 'Maaf, saya gagal merangkum data laporan saat ini.';
    }
}
