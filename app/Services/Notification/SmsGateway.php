<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;

class SmsGateway implements GatewayInterface
{
    public function send(string $to, string $text): bool
    {
        $response = Http::post('test.ru', [
            'phone' => $to,
            'msg' => $text,
        ]);

        return $response->successful();
    }
}
