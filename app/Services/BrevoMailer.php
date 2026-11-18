<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoMailer
{
    public static function sendMailable(string $toEmail, string $toName, Mailable $mailable): bool
    {
        $apiKey = config('services.brevo.key');

        if (empty($apiKey)) {
            Log::error('BrevoMailer: BREVO_API_KEY is not configured.');
            return false;
        }

        try {
            $htmlContent = $mailable->render();
            $subject = $mailable->envelope()->subject ?? 'Kohan Mart Notification';

            $response = Http::timeout(8)
                ->withHeaders([
                    'accept' => 'application/json',
                    'api-key' => $apiKey,
                    'content-type' => 'application/json',
                ])
                ->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => config('mail.from.name', 'Kohan Mart'),
                        'email' => config('mail.from.address'),
                    ],
                    'to' => [
                        [
                            'email' => $toEmail,
                            'name' => $toName ?: $toEmail,
                        ],
                    ],
                    'subject' => $subject,
                    'htmlContent' => $htmlContent,
                ]);

            if ($response->failed()) {
                Log::error('BrevoMailer API failure', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('BrevoMailer exception: ' . $e->getMessage());
            return false;
        }
    }
}