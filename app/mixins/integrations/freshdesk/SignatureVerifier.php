<?php

namespace App\Mixins\Integrations\Freshdesk;

class SignatureVerifier
{
    public function __construct(private Config $config) {}

    public function verify(string $rawBody, ?string $signatureHeader): bool
    {
        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $secret = $this->config->webhookSecret();
        if (!$secret) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);
        $provided = $this->stripPrefix($signatureHeader);

        return hash_equals($expected, $provided);
    }

    private function stripPrefix(string $header): string
    {
        if (str_starts_with($header, 'sha256=')) {
            return substr($header, 7);
        }

        return $header;
    }
}
