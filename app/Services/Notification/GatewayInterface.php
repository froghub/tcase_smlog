<?php
namespace App\Services\Notification;
interface GatewayInterface
{
    public function send(string $to, string $text): bool;
}
