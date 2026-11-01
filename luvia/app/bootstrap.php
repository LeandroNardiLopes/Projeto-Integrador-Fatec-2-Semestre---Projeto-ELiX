<?php
/**
 * Inicialização: configuração, sessão segura, carregamento automático das classes.
 */
declare(strict_types=1);

$GLOBALS['config'] = require BASE_PATH . '/app/Config/config.php';
date_default_timezone_set($GLOBALS['config']['fuso']);

// Mostra erros só no modo de desenvolvimento (APP_DEBUG=true no .env).
ini_set('display_errors', $GLOBALS['config']['debug'] ? '1' : '0');
error_reporting(E_ALL);

// Sessão segura: cookie inacessível ao JavaScript e só para este site.
session_name('LUVIASESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
ini_set('session.use_strict_mode', '1');
session_start();

// Carrega as classes das pastas Helpers, Models e Controllers pelo nome.
spl_autoload_register(function (string $classe): void {
    foreach (['Helpers', 'Models', 'Controllers'] as $pasta) {
        $arquivo = BASE_PATH . "/app/{$pasta}/{$classe}.php";
        if (is_file($arquivo)) {
            require $arquivo;
            return;
        }
    }
});

require BASE_PATH . '/app/Helpers/funcoes.php';

// Cabeçalhos básicos de segurança.
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
