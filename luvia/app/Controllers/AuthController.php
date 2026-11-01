<?php
/**
 * Login, logout e criação da senha no primeiro acesso (RF01).
 */
declare(strict_types=1);

final class AuthController
{
    public function index(): void
    {
        if (usuaria() !== null) {
            redirecionar('painel');
        }
        $pendente = Usuario::aguardandoPrimeiraSenha();
        if ($pendente !== null) {
            render('auth/primeiro_acesso', ['titulo' => 'Primeiro acesso', 'u' => $pendente], 'layout/simples');
            return;
        }
        render('auth/login', ['titulo' => 'Entrar', 'bloqueio' => Auth::bloqueado()], 'layout/simples');
    }

    public function entrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirecionar('login');
        }
        if (Auth::bloqueado() > 0) {
            avisar('erro', 'Muitas tentativas. Aguarde alguns minutos e tente de novo.');
            redirecionar('login');
        }
        $email = mb_strtolower(post('email'));
        $senha = $_POST['senha'] ?? '';
        $u = is_string($senha) && $email !== '' ? Usuario::porEmail($email) : null;

        if ($u === null || $u['senha_hash'] === Auth::SENHA_PROVISORIA || !password_verify((string)$senha, $u['senha_hash'])) {
            Auth::registrarFalha();
            registrar_log('login_falhou', 'E-mail informado: ' . mb_substr($email, 0, 120));
            avisar('erro', 'E-mail ou senha incorretos.');
            redirecionar('login');
        }
        if (password_needs_rehash($u['senha_hash'], PASSWORD_DEFAULT)) {
            Usuario::definirSenha((int)$u['id'], (string)$senha);
        }
        Auth::iniciarSessao($u);
        registrar_log('login');
        redirecionar('painel');
    }

    public function sair(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && usuaria() !== null) {
            registrar_log('logout');
            Auth::encerrarSessao();
            session_start();
            avisar('ok', 'Você saiu do sistema.');
        }
        redirecionar('login');
    }

    /** Primeiro acesso: define nome, e-mail e senha da mentora. */
    public function definir_senha(): void
    {
        $u = Usuario::aguardandoPrimeiraSenha();
        if ($u === null || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirecionar('login');
        }
        $nome = post('nome');
        $email = mb_strtolower(post('email'));
        $senha = (string)($_POST['senha'] ?? '');
        $confirma = (string)($_POST['confirma'] ?? '');

        $erro = match (true) {
            $nome === '' => 'Informe seu nome.',
            !filter_var($email, FILTER_VALIDATE_EMAIL) => 'Informe um e-mail válido.',
            mb_strlen($senha) < 8 => 'A senha precisa ter pelo menos 8 caracteres.',
            $senha !== $confirma => 'As duas senhas não são iguais.',
            default => null,
        };
        if ($erro !== null) {
            avisar('erro', $erro);
            redirecionar('login');
        }
        Usuario::atualizarPerfil((int)$u['id'], $nome, $email);
        Usuario::definirSenha((int)$u['id'], $senha);
        avisar('ok', 'Senha criada. Agora é só entrar com seu e-mail e a nova senha.');
        redirecionar('login');
    }
}
