<?php
/**
 * Development router for PHP built-in server.
 * Serves real static files directly; routes everything else through Symfony.
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If a real file exists at the requested path, let PHP serve it as-is.
if ($uri !== '/' && is_file(__DIR__ . DIRECTORY_SEPARATOR . ltrim($uri, '/'))) {
    return false;
}

// Otherwise delegate to Symfony's front controller.
require_once __DIR__ . '/index.php';
