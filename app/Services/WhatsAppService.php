<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $token;
    protected $apiUrl;

    public function __construct()
    {
        $this->token = config('services.whatsapp.token');
        $this->apiUrl = config('services.whatsapp.url', 'https://api.fonnte.com/send');
    }

    /**
     * Send message via WhatsApp Gateway
     * 
     * @param string $to Target phone number (e.g. 62812xxxx)
     * @param string $message Text message content
     * @return array|bool
     */
    public function sendMessage($to, $message)
    {
        if (empty($this->token)) {
            Log::warning('WhatsApp API Token is not set. Outgoing message log: To: ' . $to . ' | Message: ' . $message);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token
            ])->post($this->apiUrl, [
                'target' => $to,
                'message' => $message,
            ]);

            if (!$response->successful()) {
                Log::error('WhatsApp Gateway Error: ' . $response->body());
                return false;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Service Exception: ' . $e->getMessage());
            return false;
        }
    }
}
