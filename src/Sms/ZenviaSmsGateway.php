<?php

declare(strict_types=1);

namespace TigerZone\Sms;

/**
 * Envio via Zenvia SMS (API v2).
 *
 * Requer ENV:
 * - ZENVIA_API_TOKEN
 * - ZENVIA_FROM (opcional, depende da conta/shortcode; pode ficar vazio)
 *
 * Docs: https://zenvia.com
 */
final class ZenviaSmsGateway implements SmsGatewayInterface
{
    private string $apiToken;
    private string $from;

    public function __construct(string $apiToken, string $from = '')
    {
        $this->apiToken = trim($apiToken);
        $this->from = trim($from);
    }

    public function send(string $toE164, string $message): array
    {
        if ($this->apiToken === '') {
            return ['success' => false, 'error' => 'Zenvia não configurado (ZENVIA_API_TOKEN ausente).'];
        }
        if ($toE164 === '' || $message === '') {
            return ['success' => false, 'error' => 'Destino/mensagem inválidos.'];
        }

        // Zenvia geralmente aceita número com + (E.164) ou apenas dígitos dependendo da conta.
        $to = $toE164;

        $url = 'https://api.zenvia.com/v2/channels/sms/messages';
        $payloadArr = [
            'from' => $this->from !== '' ? $this->from : null,
            'to' => $to,
            'contents' => [
                ['type' => 'text', 'text' => $message],
            ],
        ];
        if ($payloadArr['from'] === null) {
            unset($payloadArr['from']);
        }
        $payload = json_encode($payloadArr, JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return ['success' => false, 'error' => 'Falha ao serializar payload.'];
        }

        $formatError = function (int $httpCode, ?string $body): string {
            $msg = 'Zenvia retornou HTTP ' . $httpCode;
            if ($body) {
                $json = json_decode($body, true);
                if (is_array($json)) {
                    $err = (string) ($json['message'] ?? $json['error'] ?? '');
                    if ($err !== '') {
                        $msg = 'Zenvia: ' . $err . ' [HTTP ' . $httpCode . ']';
                    }
                }
            }
            return $msg;
        };

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-API-TOKEN: ' . $this->apiToken,
                ],
                CURLOPT_TIMEOUT => 12,
            ]);
            $body = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false) {
                return ['success' => false, 'error' => 'Falha ao enviar SMS (cURL): ' . ($err ?: 'erro desconhecido')];
            }
            if ($code < 200 || $code >= 300) {
                return ['success' => false, 'error' => $formatError($code, (string) $body)];
            }
            return ['success' => true];
        }

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n"
                    . "X-API-TOKEN: " . $this->apiToken . "\r\n",
                'content' => $payload,
                'timeout' => 12,
            ],
        ];
        $ctx = stream_context_create($opts);
        $body = @file_get_contents($url, false, $ctx);
        $httpCode = 0;
        if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
            $httpCode = (int) $m[1];
        }
        if ($body === false) {
            return ['success' => false, 'error' => 'Falha ao enviar SMS (stream).'];
        }
        if ($httpCode && ($httpCode < 200 || $httpCode >= 300)) {
            return ['success' => false, 'error' => $formatError($httpCode, (string) $body)];
        }
        return ['success' => true];
    }
}

