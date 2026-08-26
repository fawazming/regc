<?php
/**
 * REGC — Minimal .env loader.
 * Parses KEY=VALUE lines from a .env file. Values may be:
 *   - plain        => FOO=bar
 *   - quoted       => FOO="bar baz" or FOO='bar'
 *   - comments     => lines starting with # and blank lines are ignored
 *   - inline notes => FOO=bar # comment
 * Supports ${VAR} expansion and DEFAULT_FOO fallback convention.
 * Never overwrites real environment variables (setenv false by default),
 * matching standard dotenv behavior.
 */
function env_load(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    $vars = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));

        // Strip inline comment (space + # or ;)
        if (preg_match('/^(".*"|\'.*\'|[^"\']*?)(\s*[#;].*)?$/', $value, $m)) {
            $value = $m[1];
        }

        // Unquote
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $vars[$key] = $value;
    }

    // Expand ${VAR} references
    foreach ($vars as $key => $value) {
        $vars[$key] = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/i', function ($m) use ($vars) {
            return $vars[$m[1]] ?? '';
        }, $value);
    }

    foreach ($vars as $key => $value) {
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/** Get an env value with a default fallback (or DEFAULT_<KEY>). */
function env(string $key, ?string $default = null): ?string
{
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? null;
    }
    if ($val !== null && $val !== '') {
        return $val;
    }
    // DEFAULT_<KEY> convention
    $def = getenv('DEFAULT_' . $key);
    if ($def !== false) {
        return $def;
    }
    return $default;
}