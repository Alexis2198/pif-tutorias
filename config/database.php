<?php
/**
 * Conexión PDO a MySQL en Aiven con SSL.
 * Las credenciales se leen de variables de entorno (Railway las inyecta).
 * En local, define un archivo .env o expórtalas en tu shell.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
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

    /*
     * Aiven exige TLS. El certificado de la CA se monta en certs/ca.pem.
     * En Railway, el archivo puede no existir en disco si lo pasas como
     * variable de entorno; en ese caso lo escribimos a un archivo temporal.
     */
    $caPath = __DIR__ . '/../certs/ca.pem';

    $caEnv = getenv('DB_SSL_CA');
    if ($caEnv && !file_exists($caPath)) {
        // Contenido del cert pasado como env var multilínea
        $caPath = sys_get_temp_dir() . '/aiven-ca.pem';
        file_put_contents($caPath, $caEnv);
    }

    if (file_exists($caPath)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
        // Aiven usa certificados válidos; mantener la verificación activa.
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    return new PDO($dsn, $user, $pass, $options);
}
