<?php

namespace App\Services\Notification;

class GatewayProvider
{
    public static function make(string $channel): GatewayInterface
    {
        return match ($channel) {
            'sms' => new SmsGateway(),
            'email' => new EmailGateway(),
            default => throw new \InvalidArgumentException("Неизвестный канал связи: {$channel}"),
        };
    }
}
