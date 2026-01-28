<?php

declare(strict_types=1);

namespace TigerZone\Mail;

/**
 * Envio simples via mail() do PHP.
 * Para hospedagens compartilhadas, normalmente é o caminho mais compatível.
 */
final class MailService
{
    /** @return array{success: bool, error?: string} */
    public function send(string $to, string $subject, string $html, ?string $text = null): array
    {
        $fromAddr = (string) \config('app.mail.from_address', 'no-reply@localhost');
        $fromName = (string) \config('app.mail.from_name', 'TigerZone');
        if ($to === '' || $subject === '' || $html === '') {
            return ['success' => false, 'error' => 'Parâmetros de e-mail inválidos.'];
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $this->formatFrom($fromName, $fromAddr);

        $ok = @mail($to, $subject, $html, implode("\r\n", $headers));
        if (!$ok) {
            return ['success' => false, 'error' => 'Falha ao enviar e-mail via mail().'];
        }
        return ['success' => true];
    }

    private function formatFrom(string $name, string $email): string
    {
        $n = trim($name);
        $e = trim($email);
        if ($n === '') return $e;
        // evita caracteres problemáticos em header
        $n = preg_replace('/[\r\n]+/', ' ', $n) ?? $n;
        return sprintf('"%s" <%s>', addslashes($n), $e);
    }
}

