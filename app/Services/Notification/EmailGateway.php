<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;

class EmailGateway implements GatewayInterface
{
    public function send(string $to, string $text): bool
    {
        $response = Http::post('test.ru', [
            'email_to' => $to,
            'subject' => 'Уведомление от сервиса',
            'body' => $text,
        ]);

        return $response->successful();
    }
}
