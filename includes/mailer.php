<?php
/**
 * REGC — Minimal SMTP client (no external dependencies).
 * Supports:
 *   - tls  (STARTTLS, port 587)   [default]
 *   - ssl  (implicit TLS, port 465)
 *   - none (plain, local SMTP / testing)
 * AUTH LOGIN with AUTH PLAIN fallback. Returns true on success.
 * The last failure reason is available via smtp_last_error().
 */
function smtp_last_error(): ?string
{
    return $GLOBALS['__smtp_last_error'] ?? null;
}

function send_mail(array $cfg, string $to, string $subject, string $htmlBody): bool
{
    $GLOBALS['__smtp_last_error'] = null;
    $fail = function (string $msg) {
        $GLOBALS['__smtp_last_error'] = $msg;
        log_msg('SMTP: ' . $msg);
        return false;
    };

    $host = $cfg['smtp_host'] ?? '';
    $port = (int)($cfg['smtp_port'] ?? 587);
    $user = $cfg['smtp_user'] ?? '';
    $pass = $cfg['smtp_pass'] ?? '';
    $secure = $cfg['smtp_secure'] ?? 'tls';
    $from = $cfg['mail_from'] ?? $cfg['smtp_user'] ?? 'no-reply@' . APP_NAME;
    $fromName = $cfg['mail_from_name'] ?? APP_NAME;

    if ($host === '') {
        return $fail('SMTP host not configured');
    }

    $remote = ($secure === 'ssl') ? 'ssl://' . $host : $host;

    // SSL context: send SNI + peer_name so virtual-hosted mail servers present
    // the correct certificate; disable peer verification because many mail
    // servers use self-signed or hostname-mismatched certs.
    $ctx = stream_context_create([
        'ssl' => [
            'peer_name' => $host,
            'SNI_enabled' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ]);

    $fp = @stream_socket_client($remote . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        return $fail("Connect to $host:$port failed: $errstr");
    }
    stream_set_timeout($fp, 25);

    // Read a full SMTP response (handles multi-line 250-... continuation).
    $readResp = function () use (&$fp): array {
        $lines = [];
        $code = '';
        while (true) {
            $line = fgets($fp, 515);
            if ($line === false) {
                break;
            }
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }
            $lines[] = $line;
            if ($code === '') {
                $code = substr($line, 0, 3);
            }
            // A hyphen after the code means more lines follow.
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        return ['code' => (int)$code, 'lines' => $lines];
    };

    $send = function (string $c) use (&$fp): void {
        fwrite($fp, $c . "\r\n");
    };

    $expect = function (int $code) use ($readResp, $fail): bool {
        $r = $readResp();
        if ($r['code'] !== $code) {
            $GLOBALS['__smtp_last_error'] = 'Expected ' . $code . ', got: ' . implode(' | ', $r['lines']);
            log_msg('SMTP unexpected response', ['expect' => $code, 'got' => $r['lines']]);
            return false;
        }
        return true;
    };

    $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';

    // Banner
    $readResp();

    // EHLO (multi-line)
    $send('EHLO ' . $serverName);
    if (!$expect(250)) {
        fclose($fp);
        return false;
    }

    // STARTTLS: send the command, expect 220, THEN enable crypto.
    if ($secure === 'tls') {
        $send('STARTTLS');
        if (!$expect(220)) {
            fclose($fp);
            return false;
        }
        // Try the broadest TLS method first, then narrower ones.
        $tlsMethods = [
            STREAM_CRYPTO_METHOD_TLS_CLIENT,
            STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
            STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
            STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT,
        ];
        $tlsOk = false;
        $tlsErr = '';
        foreach ($tlsMethods as $method) {
            $tlsOk = @stream_socket_enable_crypto($fp, true, $method);
            if ($tlsOk) {
                break;
            }
            $last = error_get_last();
            $tlsErr = $last['message'] ?? '';
            while ($oe = openssl_error_string()) {
                $tlsErr .= ($tlsErr !== '' ? ' | ' : '') . $oe;
            }
            // A failed TLS negotiation can corrupt the socket; reconnect and retry.
            fclose($fp);
            $fp = @stream_socket_client($remote . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
            if (!$fp) {
                return $fail("Reconnect failed after TLS attempt: $errstr");
            }
            stream_set_timeout($fp, 25);
            $readResp();
            $send('EHLO ' . $serverName);
            if (!$expect(250)) {
                fclose($fp);
                return false;
            }
            $send('STARTTLS');
            if (!$expect(220)) {
                fclose($fp);
                return false;
            }
        }
        if (!$tlsOk) {
            fclose($fp);
            return $fail('STARTTLS handshake failed: ' . ($tlsErr !== '' ? $tlsErr : 'server refused TLS (try port 465 with Security=ssl, or Security=none)'));
        }
        $send('EHLO ' . $serverName);
        if (!$expect(250)) {
            fclose($fp);
            return false;
        }
    }

    // Authentication: AUTH LOGIN, with AUTH PLAIN fallback.
    if ($user !== '') {
        $send('AUTH LOGIN');
        $r = $readResp();
        if ($r['code'] === 334) {
            $send(base64_encode($user));
            if (!$expect(334)) { fclose($fp); return false; }
            $send(base64_encode($pass));
            if (!$expect(235)) { fclose($fp); return false; }
        } elseif ($r['code'] === 504 || $r['code'] === 502) {
            // LOGIN unsupported — try PLAIN (NUL-user-NUL-pass)
            $send('AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $pass));
            if (!$expect(235)) { fclose($fp); return false; }
        } else {
            fclose($fp);
            return $fail('AUTH rejected (' . implode(' | ', $r['lines']) . ')');
        }
    }

    $send('MAIL FROM:<' . $from . '>');
    if (!$expect(250)) { fclose($fp); return false; }

    $send('RCPT TO:<' . $to . '>');
    if (!$expect(250)) { fclose($fp); return false; }

    $send('DATA');
    if (!$expect(354)) { fclose($fp); return false; }

    $headers = [
        'From: ' . $fromName . ' <' . $from . '>',
        'To: ' . $to,
        'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8'),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        'Date: ' . date('r'),
        'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . APP_NAME . '>',
    ];
    $body = chunk_split(base64_encode($htmlBody));

    fwrite($fp, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");
    if (!$expect(250)) { fclose($fp); return false; }

    $send('QUIT');
    fclose($fp);
    return true;
}