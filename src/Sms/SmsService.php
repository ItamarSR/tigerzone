<?php

declare(strict_types=1);

namespace TigerZone\Sms;

final class SmsService
{
    private SmsGatewayInterface $gateway;

    public function __construct(?SmsGatewayInterface $gateway = null)
    {
        $driver = (string) \config('app.sms.driver', 'simulated');
        if ($gateway) {
            $this->gateway = $gateway;
        } else {
            if ($driver === 'twilio') {
                $sid = (string) \config('app.sms.twilio.account_sid', '');
                $token = (string) \config('app.sms.twilio.auth_token', '');
                $from = (string) \config('app.sms.twilio.from', '');
                $this->gateway = new TwilioSmsGateway($sid, $token, $from);
            } else {
                $this->gateway = new SimulatedSmsGateway();
            }
        }
    }

    /** @return array{success: bool, error?: string} */
    public function sendVerificationCode(string $toE164, string $code): array
    {
        $brand = (string) \config('app.name', 'TigerZone');
        $msg = $brand . ': seu código de confirmação é ' . $code . '.';
        return $this->gateway->send($toE164, $msg);
    }
}

