<?php
/**
 * Funções de apoio usadas em todo o sistema:
 * escapar texto, montar links, mensagens, CSRF, formatação de datas e valores.
 */
declare(strict_types=1);

/** Escapa texto para HTML (proteção contra XSS). Use em TODA saída. */
function e(mixed $valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Monta um link interno: url('alunos', 'ver', ['id' => 3]) */
function url(string $pagina, ?string $acao = null, array $params = []): string
{
    $q = ['p' => $pagina];
    if ($acao !== null && $acao !== 'index') {
        $q['a'] = $acao;
    }
    return 'index.php?' . http_build_query($q + $params);
}

function redirecionar(string $pagina, ?string $acao = null, array $params = [], string $ancora = ''): never
{
    header('Location: ' . url($pagina, $acao, $params) . ($ancora !== '' ? '#' . $ancora : ''));
    exit;
}

/** Volta para a página anterior (ou para o painel). Só aceita links internos. */
function voltar(string $padrao = 'index.php'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $destino = $padrao;
    if ($ref !== '') {
        $partes = parse_url($ref);
        $hostRef = ($partes['host'] ?? '') . (isset($partes['port']) ? ':' . $partes['port'] : '');
        if (!isset($partes['host']) || $hostRef === ($_SERVER['HTTP_HOST'] ?? '')) {
            $destino = basename($partes['path'] ?? 'index.php') . (isset($partes['query']) ? '?' . $partes['query'] : '');
            if (isset($partes['fragment'])) {
                $destino .= '#' . $partes['fragment'];
            }
        }
    }
    header('Location: ' . $destino);
    exit;
}

/** Mensagens que aparecem uma vez no topo da página. */
function avisar(string $tipo, string $texto): void
{
    $_SESSION['avisos'][] = ['tipo' => $tipo, 'texto' => $texto];
}

function pegar_avisos(): array
{
    $a = $_SESSION['avisos'] ?? [];
    unset($_SESSION['avisos']);
    return $a;
}

/* ---------------- CSRF: protege todos os formulários ---------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_validar(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(400);
        exit('Formulário expirado ou inválido. Volte, atualize a página e tente de novo.');
    }
}

/** Ações que mudam dados só aceitam POST (com o token CSRF já conferido). */
function exigir_post(string $pagina = 'painel'): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirecionar($pagina);
    }
}

/* ---------------- Leitura de dados do formulário ---------------- */
function post(string $campo, string $padrao = ''): string
{
    $v = $_POST[$campo] ?? $padrao;
    return is_string($v) ? trim($v) : $padrao;
}

function post_int(string $campo): ?int
{
    $v = $_POST[$campo] ?? '';
    return (is_string($v) && ctype_digit($v)) ? (int)$v : null;
}

function get_int(string $campo): ?int
{
    $v = $_GET[$campo] ?? '';
    return (is_string($v) && ctype_digit($v)) ? (int)$v : null;
}

function vazio_null(string $v): ?string
{
    return $v === '' ? null : $v;
}

function data_valida(string $v): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return $d !== false && $d->format('Y-m-d') === $v;
}

function hora_valida(string $v): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v);
}

function url_valida(string $v): bool
{
    return filter_var($v, FILTER_VALIDATE_URL) !== false && preg_match('#^https?://#i', $v);
}

/* ---------------- Formatação ---------------- */
function data_br(?string $data): string
{
    if (!$data) {
        return '—';
    }
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : '—';
}

function data_curta(?string $data): string
{
    if (!$data) {
        return '—';
    }
    $dias = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    $ts = strtotime($data);
    return $dias[(int)date('w', $ts)] . ', ' . date('d/m', $ts);
}

function hora_br(?string $hora): string
{
    return $hora ? substr($hora, 0, 5) : '';
}

function moeda(mixed $valor): string
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function pct(mixed $valor): string
{
    return $valor === null ? '—' : number_format((float)$valor, 0, ',', '.') . '%';
}

/** Selo colorido para status. */
function selo(string $texto, string $tipo = 'neutro'): string
{
    return '<span class="selo selo-' . e($tipo) . '">' . e($texto) . '</span>';
}

const NIVEIS = [
    'pessoal' => 'Pessoal', 'social' => 'Social', 'profissional' => 'Profissional',
    'leadership' => 'Leadership', 'expand' => 'Expand',
];

const MODALIDADES = ['online' => 'Online', 'gravada' => 'Gravada', 'hibrida' => 'Híbrida'];

const FORMAS_PAGAMENTO = ['Pix', 'Transferência', 'Boleto', 'Cartão', 'Dinheiro'];

/** O módulo existe nesta versão do sistema? (usado para esconder links) */
function modulo_ativo(string $pagina): bool
{
    return isset($GLOBALS['rotas'][$pagina]);
}

/** Usuária logada (ou null). */
function usuaria(): ?array
{
    return $_SESSION['usuaria'] ?? null;
}

/** Desenha uma view dentro do layout padrão. */
function render(string $view, array $dados = [], string $layout = 'layout/base'): void
{
    extract($dados, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/app/Views/' . $view . '.php';
    $conteudo = ob_get_clean();
    require BASE_PATH . '/app/Views/' . $layout . '.php';
}

/** Registra uma ação no log de auditoria (RF03). Nunca interrompe o sistema. */
function registrar_log(string $acao, ?string $detalhe = null): void
{
    try {
        Db::executar('INSERT INTO logs_acesso (usuario_id, acao, detalhe) VALUES (?, ?, ?)',
            [usuaria()['id'] ?? null, $acao, $detalhe === null ? null : mb_substr($detalhe, 0, 255)]);
    } catch (Throwable $t) {
        error_log('Falha ao registrar log: ' . $t->getMessage());
    }
}
