<?php
/**
 * Conexión PDO a MySQL en Aiven con SSL.
 * Las credenciales se leen de variables de entorno (Railway las inyecta).
 * En local, define un archivo .env o expórtalas en tu shell.
 */

declare(strict_types=1);

function db(): Pdo\Mysql {
    static $pdo = null;
    if ($pdo instanceof Pdo\Mysql) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'tutorias_db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $useSsl = filter_var(getenv('DB_SSL') ?: 'false', FILTER_VALIDATE_BOOL);
    $caPath = __DIR__ . '/../certs/ca.pem';

    if ($useSsl && is_readable($caPath)) {
        $options[Pdo\Mysql::ATTR_SSL_CA] = $caPath;
        $options[Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    return $pdo = Pdo\Mysql::connect($dsn, $user, $pass, $options);
}
