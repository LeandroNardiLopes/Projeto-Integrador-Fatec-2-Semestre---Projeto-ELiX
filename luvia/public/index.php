<?php
/**
 * Ponto de entrada do Luvia. Toda página passa por aqui:
 *   index.php?p=alunos&a=ver&id=1
 * p = módulo (página), a = ação dentro do módulo.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

$GLOBALS['rotas'] = require BASE_PATH . '/app/rotas.php';
$rotas = $GLOBALS['rotas'];

$pagina = is_string($_GET['p'] ?? null) ? $_GET['p'] : 'painel';
$acao   = is_string($_GET['a'] ?? null) ? $_GET['a'] : 'index';

if (!isset($rotas[$pagina]) || !in_array($acao, $rotas[$pagina]['acoes'], true)) {
    http_response_code(404);
    render('erros/404', ['titulo' => 'Página não encontrada'], 'layout/simples');
    exit;
}

$rota = $rotas[$pagina];

// Toda página, menos o login, exige a mentora logada (e a sessão em dia).
if (empty($rota['publica'])) {
    Auth::exigirLogin();
}

// Todo envio de formulário (POST) precisa do token CSRF.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
}

try {
    $controller = new $rota['controller']();
    $controller->$acao();
} catch (PDOException $e) {
    if (in_array((int)$e->getCode(), [1045, 1049, 2002, 2006], true)) {
        http_response_code(500);
        render('erros/banco', ['titulo' => 'Banco de dados indisponível'], 'layout/simples');
        exit;
    }
    throw $e;
}
