<?php

declare(strict_types=1);

namespace TigerZone\Sms;

/**
 * Gateway simulado: não envia SMS de verdade.
 * Útil para ambientes sem credenciais; o controller pode exibir o código em flash se permitido.
 */
final class SimulatedSmsGateway implements SmsGatewayInterface
{
    public function send(string $toE164, string $message): array
    {
        // No-op
        return ['success' => true];
    }
}

