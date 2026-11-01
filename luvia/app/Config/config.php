<?php
/**
 * Configuração do sistema.
 * Lê o arquivo .env (na pasta raiz do projeto). Se ele não existir,
 * usa os valores padrão do XAMPP (usuário root, sem senha).
 */
declare(strict_types=1);

function carregar_env(string $arquivo): array
{
    $valores = [];
    if (!is_file($arquivo)) {
        return $valores;
    }
    foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#' || !str_contains($linha, '=')) {
            continue;
        }
        [$chave, $valor] = explode('=', $linha, 2);
        $valores[trim($chave)] = trim(trim($valor), "\"'");
    }
    return $valores;
}

$env = carregar_env(BASE_PATH . '/.env');

return [
    'app_nome'      => 'Luvia',
    'debug'         => ($env['APP_DEBUG'] ?? 'false') === 'true',
    'fuso'          => $env['APP_FUSO'] ?? 'America/Sao_Paulo',
    'sessao_minutos'=> (int)($env['SESSAO_MINUTOS'] ?? 60),
    'db' => [
        'host'  => $env['DB_HOST'] ?? '127.0.0.1',
        'porta' => $env['DB_PORTA'] ?? '3306',
        'nome'  => $env['DB_NOME'] ?? 'luvia',
        'usuario' => $env['DB_USUARIO'] ?? 'root',
        'senha' => $env['DB_SENHA'] ?? '',
    ],
];
