<?php

declare(strict_types=1);

namespace TigerZone\Sms;

interface SmsGatewayInterface
{
    /** @return array{success: bool, error?: string} */
    public function send(string $toE164, string $message): array;
}

