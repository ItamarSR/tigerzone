<?php

declare(strict_types=1);

namespace TigerZone\Payment;

/**
 * Gateway simulado. Estrutura preparada para integração PIX.
 */
class SimulatedGateway implements PaymentGatewayInterface
{
    public function createPayment(float $amount, array $metadata = []): array
    {
        return [
            'success' => true,
            'payment_id' => 'SIM-' . bin2hex(random_bytes(8)),
            'qr_code' => null,
        ];
    }

    public function checkStatus(string $paymentId): array
    {
        return [
            'status' => 'approved',
            'payment_id' => $paymentId,
        ];
    }

    public function confirmTransaction(string $paymentId, array $payload = []): array
    {
        return [
            'success' => true,
            'transaction_id' => null,
        ];
    }
}
