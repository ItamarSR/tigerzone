<?php

declare(strict_types=1);

namespace TigerZone\Sms;

/**
 * Envio via Twilio SMS (REST API).
 *
 * Requer ENV:
 * - TWILIO_ACCOUNT_SID
 * - TWILIO_AUTH_TOKEN
 * - TWILIO_FROM (ex.: +14155552671)
 */
final class TwilioSmsGateway implements SmsGatewayInterface
{
    private string $accountSid;
    private string $authToken;
    private string $from;

    public function __construct(string $accountSid, string $authToken, string $from)
    {
        $this->accountSid = trim($accountSid);
        $this->authToken = trim($authToken);
        $this->from = trim($from);
    }

    public function send(string $toE164, string $message): array
    {
        if ($this->accountSid === '' || $this->authToken === '' || $this->from === '') {
            return ['success' => false, 'error' => 'Twilio não configurado (SID/TOKEN/FROM ausentes).'];
        }
        if ($toE164 === '' || $message === '') {
            return ['success' => false, 'error' => 'Destino/mensagem inválidos.'];
        }

        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($this->accountSid) . '/Messages.json';
        $payload = http_build_query([
            'To' => $toE164,
            'From' => $this->from,
            'Body' => $message,
        ]);

        $formatTwilioError = function (int $httpCode, ?string $body): string {
            $msg = 'Twilio retornou HTTP ' . $httpCode;
            if ($body) {
                $json = json_decode($body, true);
                if (is_array($json)) {
                    $twMsg = (string) ($json['message'] ?? '');
                    $twCode = $json['code'] ?? null;
                    if ($twMsg !== '') {
                        $msg = 'Twilio: ' . $twMsg;
                        if ($twCode !== null && $twCode !== '') {
                            $msg .= ' (código ' . $twCode . ')';
                        }
                        $msg .= ' [HTTP ' . $httpCode . ']';
                    }
                }
            }
            return $msg;
        };

        // Preferir cURL quando disponível
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $this->accountSid . ':' . $this->authToken,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
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
                return ['success' => false, 'error' => $formatTwilioError($code, (string) $body)];
            }
            return ['success' => true];
        }

        // Fallback: streams
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                    . "Authorization: Basic " . base64_encode($this->accountSid . ':' . $this->authToken) . "\r\n",
                'content' => $payload,
                'timeout' => 12,
            ],
        ];
        $ctx = stream_context_create($opts);
        $body = @file_get_contents($url, false, $ctx);

        // Tenta extrair status code
        $httpCode = 0;
        if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
            $httpCode = (int) $m[1];
        }
        if ($body === false) {
            return ['success' => false, 'error' => 'Falha ao enviar SMS (stream).'];
        }
        if ($httpCode && ($httpCode < 200 || $httpCode >= 300)) {
            return ['success' => false, 'error' => $formatTwilioError($httpCode, (string) $body)];
        }
        return ['success' => true];
    }
}

