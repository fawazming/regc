<?php
/**
 * REGC — Rivo payment client.
 *
 * Docs: rivo.md
 * Auth: X-API-KEY header.
 *
 * Note: Only the public-facing storefront may know whether Rivo is
 * enabled. The API key and webhook secret always stay server-side and
 * are managed from the Admin -> Settings page.
 */
class Rivo
{
    private string $base = 'https://api.rivo.rayyantech.com.ng';
    private string $key;
    private string $webhookSecret;

    public function __construct(string $key = '', string $webhookSecret = '')
    {
        $this->key = $key;
        $this->webhookSecret = $webhookSecret;
    }

    /** Create a payment session; returns the full response array. */
    public function create(array $payload): array
    {
        return $this->call('POST', '/api/v1/payment/create', $payload);
    }

    /** Server-side verification of a payment by reference. */
    public function verify(string $reference): array
    {
        return $this->call('POST', '/api/v1/payment/verify', ['reference' => $reference]);
    }

    /** Poll lifecycle status of a session. */
    public function status(string $reference): array
    {
        return $this->call('GET', '/api/v1/payment/status/' . rawurlencode($reference));
    }

    private function call(string $method, string $path, array $payload = []): array
    {
        $url = $this->base . $path;
        $ch = curl_init($url);
        $headers = [
            'X-API-KEY: ' . $this->key,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        // PHP 8.0+ closes the handle automatically; curl_close() is a no-op/deprecated.

        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            $data = ['raw' => $raw];
        }
        $data['_http'] = $http;
        $data['_curl_error'] = $err;
        return $data;
    }

    /**
     * Verify the Rivo webhook signature.
     * Signature format: X-PGSP-Signature: t=<ts>,v1=<hmac-sha256>
     * Recompute HMAC-SHA256 over timestamp + "." + raw body using the webhook secret.
     */
    public function verifyWebhook(string $signatureHeader, string $rawBody): bool
    {
        if (empty($this->webhookSecret) || empty($signatureHeader)) {
            return false;
        }
        if (!preg_match('/t=(\d+),v1=([0-9a-f]+)/', $signatureHeader, $m)) {
            return false;
        }
        $ts = $m[1];
        $sig = $m[2];
        $expected = hash_hmac('sha256', $ts . '.' . $rawBody, $this->webhookSecret);
        return hash_equals($expected, $sig);
    }
}

/** Build a Rivo client from the current settings. */
function rivo(): Rivo
{
    $s = get_settings();
    return new Rivo(
        (string)($s['rivo_api_key'] ?? ''),
        (string)($s['rivo_webhook_secret'] ?? '')
    );
}