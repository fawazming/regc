<?php
/**
 * REGC — Supabase REST (PostgREST) client.
 * Communicates with Supabase via its REST API using cURL.
 * All keys stay server-side.
 */
class Supabase
{
    private string $url;
    private string $key;

    public function __construct(string $url, string $key)
    {
        $this->url = rtrim($url, '/') . '/rest/v1';
        $this->key = $key;
    }

    /**
     * @param string $method GET|POST|PATCH|DELETE
     * @param string $table  table name
     * @param array  $query  PostgREST query params (filters etc.)
     * @param mixed  $body   payload for POST/PATCH
     * @param bool   $representation return=representation header
     */
    public function request(string $method, string $table, array $query = [], $body = null, bool $representation = true): array
    {
        $url = $this->url . '/' . $table;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($representation) {
            $headers[] = 'Prefer: return=representation';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        // PHP 8.0+ closes the handle automatically; curl_close() is a no-op/deprecated.

        if ($err) {
            return ['error' => 'cURL: ' . $err, 'http' => $http];
        }

        $decoded = json_decode($raw, true);
        if ($http >= 400) {
            $msg = is_array($decoded) ? json_encode($decoded) : $raw;
            return ['error' => 'Supabase (' . $http . '): ' . $msg, 'http' => $http, 'raw' => $raw];
        }

        return ['data' => $decoded, 'http' => $http];
    }

    public function select(string $table, array $query = []): array
    {
        return $this->request('GET', $table, $query);
    }

    public function insert(string $table, array $rows, bool $representation = true): array
    {
        return $this->request('POST', $table, [], $rows, $representation);
    }

    public function update(string $table, array $row, array $query): array
    {
        return $this->request('PATCH', $table, $query, $row);
    }

    public function delete(string $table, array $query): array
    {
        return $this->request('DELETE', $table, $query, null, true);
    }
}