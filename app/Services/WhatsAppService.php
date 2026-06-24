<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $token;
    protected $apiUrl;
    protected $senderNumber;

    public function __construct()
    {
        $this->token = config('services.whatsapp.token');
        $this->apiUrl = config('services.whatsapp.url', 'https://api.fonnte.com/send');
        $this->senderNumber = config('services.whatsapp.sender_number');
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
            // Detect if using Fonnte or MPWA (M-Pedia) based on URL
            if (str_contains($this->apiUrl, 'fonnte.com')) {
                $response = Http::withHeaders([
                    'Authorization' => $this->token
                ])->post($this->apiUrl, [
                    'target' => $to,
                    'message' => $message,
                ]);
            } else {
                // MPWA Format: requires api_key, sender (device number), number (destination), message
                $response = Http::post($this->apiUrl, [
                    'api_key' => $this->token,
                    'sender'  => $this->senderNumber,
                    'number'  => $to,
                    'message' => $message,
                ]);
            }

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
