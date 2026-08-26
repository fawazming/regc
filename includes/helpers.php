<?php
/**
 * REGC — Helpers
 */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('e_js')) {
    /**
     * JSON-encode a value AND escape it for safe use inside a double-quoted
     * HTML attribute (e.g. an inline onclick handler).
     * json_encode emits double quotes, which would otherwise break the
     * surrounding HTML attribute; htmlspecialchars converts them to &quot;.
     */
    function e_js($value): string
    {
        return htmlspecialchars(
            json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('money')) {
    function money($value): string
    {
        return '₦' . number_format((float)$value, 0);
    }
}

if (!function_exists('json_out')) {
    function json_out($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('read_json_input')) {
    function read_json_input(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = explode(',', $_SERVER[$k])[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('random_str')) {
    function random_str(int $length = 10): string
    {
        return substr(strtoupper(bin2hex(random_bytes(16))), 0, $length);
    }
}

if (!function_exists('generate_order_no')) {
    function generate_order_no(): string
    {
        return 'REGC-' . date('ymd') . '-' . random_str(5);
    }
}

if (!function_exists('is_post')) {
    function is_post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

/* ---------------- CSRF ---------------- */

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): void
    {
        $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
            json_out(['ok' => false, 'error' => 'Invalid or expired security token. Please try again.'], 419);
        }
    }
}

/* ---------------- Logging ---------------- */

if (!function_exists('log_msg')) {
    function log_msg(string $msg, array $context = []): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg
            . (empty($context) ? '' : ' ' . json_encode($context))
            . PHP_EOL;
        @file_put_contents(dirname(__DIR__) . '/logs/app.log', $line, FILE_APPEND);
    }
}