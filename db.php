<?php

/*
|--------------------------------------------------------------------------
| Load .env for local development
|--------------------------------------------------------------------------
| parse_ini_file() returns an array but does NOT populate the environment.
| We push each value into putenv() so getenv() works everywhere.
|--------------------------------------------------------------------------
*/

if (getenv('APP_ENV') !== 'production') {

    $envFile = __DIR__ . '/.env';

    if (!file_exists($envFile)) {
        die(".env file not found.");
    }

    $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);

    if (!$env) {
        die("Unable to read .env file.");
    }

    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}


/*
|--------------------------------------------------------------------------
| Database configuration
|--------------------------------------------------------------------------
| Local development:
|   Reads DATABASE_URL and APP_ENV from .env
|
| Production (Render):
|   Reads DATABASE_URL and APP_ENV from Render environment variables
|--------------------------------------------------------------------------
*/

$databaseUrl = getenv('DATABASE_URL');
$appEnv = getenv('APP_ENV') ?: 'development';

if (!$databaseUrl) {
    die("DATABASE_URL is not configured.");
}


// Parse PostgreSQL connection URL
$dbparams = parse_url($databaseUrl);

$host   = $dbparams['host'];
$port   = $dbparams['port'] ?? 5432;
$user   = $dbparams['user'];
$pass   = $dbparams['pass'];
$dbname = ltrim($dbparams['path'], '/');


// Build PDO connection string
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";


// Supabase production connection requires SSL
if ($appEnv === 'production') {
    $dsn .= ";sslmode=require";
}


try {

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {

    die("Database connection failed: " . $e->getMessage());
}
