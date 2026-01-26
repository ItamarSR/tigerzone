<?php

declare(strict_types=1);

namespace TigerZone\Payment;

/**
 * Interface abstrata para gateway de pagamento.
 * Preparada para futura integração PIX – nenhuma lógica real nesta fase.
 */
interface PaymentGatewayInterface
{
    /**
     * Cria intenção de pagamento (ex.: PIX).
     * @param float $amount Valor em reais
     * @param array $metadata Dados adicionais (user_id, tipo, etc.)
     * @return array { success, payment_id?, qr_code?, error? }
     */
    public function createPayment(float $amount, array $metadata = []): array;

    /**
     * Verifica status de uma transação.
     * @param string $paymentId ID externo do pagamento
     * @return array { status: 'pending'|'approved'|'rejected'|'expired', ... }
     */
    public function checkStatus(string $paymentId): array;

    /**
     * Confirma/registra transação no sistema interno (carteira, etc.).
     * @param string $paymentId
     * @param array $payload Dados retornados pelo gateway
     * @return array { success, transaction_id?, error? }
     */
    public function confirmTransaction(string $paymentId, array $payload = []): array;
}
