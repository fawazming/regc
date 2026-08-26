<?php
/**
 * REGC — GitHub webhook deploy.
 *
 * Endpoint: /wh
 *
 * Triggered by a GitHub "push" webhook (and responds to "ping"). Verifies the
 * X-Hub-Signature-256 HMAC using the configured secret, then runs `git pull`
 * in the project root so the site auto-updates from the repository.
 *
 * Security:
 *  - Only accepts POST.
 *  - Verifies HMAC-SHA256 signature before doing anything.
 *  - Ignores non-push events (responds OK to ping).
 *  - Uses a lock file so pulls never run concurrently.
 *  - Never passes untrusted input into the shell.
 */

define('WEBHOOK_SECRET', env('WEBHOOK_SECRET', 'rayyan'));
define('WEBHOOK_LOCK', dirname(__DIR__) . '/logs/webhook.lock');
define('WEBHOOK_LOG', dirname(__DIR__) . '/logs/webhook.log');

function webhook_log(string $msg, array $ctx = []): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg
        . (empty($ctx) ? '' : ' ' . json_encode($ctx))
        . PHP_EOL;
    @file_put_contents(WEBHOOK_LOG, $line, FILE_APPEND);
}

function verify_hub_signature(string $secret, string $signatureHeader, string $body): bool
{
    if ($signatureHeader === '' || !str_starts_with($signatureHeader, 'sha256=')) {
        return false;
    }
    $sig = substr($signatureHeader, 7);
    $expected = hash_hmac('sha256', $body, $secret);
    return hash_equals($expected, $sig);
}

function webhook_handle(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    if (!is_post()) {
        json_out(['ok' => false, 'error' => 'Method not allowed'], 405);
    }

    $body = (string)file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
    $delivery = $_SERVER['HTTP_X_GITHUB_DELIVERY'] ?? '';

    if (!verify_hub_signature(WEBHOOK_SECRET, $signature, $body)) {
        webhook_log('Signature verification FAILED', ['delivery' => $delivery, 'ip' => client_ip()]);
        json_out(['ok' => false, 'error' => 'Invalid signature'], 401);
    }

    webhook_log('Webhook received', ['event' => $event, 'delivery' => $delivery]);

    // GitHub pings the endpoint when a webhook is created/tested.
    if ($event === 'ping') {
        json_out(['ok' => true, 'message' => 'pong']);
    }

    // Only run deploys on push events.
    if ($event !== 'push') {
        json_out(['ok' => true, 'message' => 'ignored event ' . $event]);
    }

    // Lock to prevent concurrent deploys.
    if (is_file(WEBHOOK_LOCK)) {
        json_out(['ok' => false, 'error' => 'Deploy already in progress'], 409);
    }
    file_put_contents(WEBHOOK_LOCK, (string)time());

    $projectRoot = dirname(__DIR__);
    $output = [];
    $code = 1;

    try {
        if (!is_dir($projectRoot . '/.git')) {
            webhook_log('Not a git repository; nothing to pull', ['root' => $projectRoot]);
            json_out(['ok' => false, 'error' => 'Not a git repository'], 500);
        }

        $prevDir = getcwd();
        chdir($projectRoot);
        // 2>&1 captures stderr too. No user input reaches the shell here.
        exec('git pull 2>&1', $output, $code);
        chdir($prevDir);

        webhook_log('git pull finished', ['code' => $code, 'output' => implode("\n", $output)]);
        if ($code === 0) {
            json_out(['ok' => true, 'output' => $output]);
        }
        json_out(['ok' => false, 'error' => 'git pull failed', 'output' => $output], 500);
    } finally {
        @unlink(WEBHOOK_LOCK);
    }
}