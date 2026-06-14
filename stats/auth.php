<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

function rf_stats_auth_required(string $message = 'RandaleFUNK Statistik'): never
{
    header('WWW-Authenticate: Basic realm="' . $message . '"');
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Zugriff nur mit Statistik-Passwort.';
    exit;
}

function rf_stats_basic_auth_credentials(): array
{
    $user = (string) ($_SERVER['PHP_AUTH_USER'] ?? '');
    $password = (string) ($_SERVER['PHP_AUTH_PW'] ?? '');

    if ($user !== '' || $password !== '') {
        return [$user, $password];
    }

    $header = (string) (
        $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? ''
    );

    if (!preg_match('/^Basic\s+(.+)$/i', $header, $matches)) {
        return ['', ''];
    }

    $decoded = base64_decode($matches[1], true);

    if (!is_string($decoded) || !str_contains($decoded, ':')) {
        return ['', ''];
    }

    return explode(':', $decoded, 2);
}

function rf_stats_require_auth(): void
{
    $config = rf_stats_config();
    $auth = $config['auth'] ?? [];
    $expectedUser = (string) ($auth['user'] ?? '');
    $expectedHash = (string) ($auth['password_hash'] ?? '');
    $expectedPassword = (string) ($auth['password'] ?? '');

    if ($expectedUser === '' || ($expectedHash === '' && $expectedPassword === '')) {
        header('HTTP/1.1 503 Service Unavailable');
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Statistik-Dashboard ist noch nicht freigeschaltet. STATS_AUTH_USER und STATS_AUTH_PASSWORD fehlen.';
        exit;
    }

    [$givenUser, $givenPassword] = rf_stats_basic_auth_credentials();
    $passwordMatches = $expectedHash !== ''
        ? password_verify($givenPassword, $expectedHash)
        : hash_equals($expectedPassword, $givenPassword);

    if (!hash_equals($expectedUser, $givenUser) || !$passwordMatches) {
        rf_stats_auth_required();
    }
}
