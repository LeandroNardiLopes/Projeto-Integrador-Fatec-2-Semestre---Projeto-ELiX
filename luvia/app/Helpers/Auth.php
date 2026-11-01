<?php
/**
 * Auth: login, logout e controle de sessão da mentora (RF01).
 */
declare(strict_types=1);

final class Auth
{
    /** Hash provisório do script SQL: indica que a senha ainda não foi criada. */
    public const SENHA_PROVISORIA = 'TROCAR_PELO_HASH_GERADO_PELA_APLICACAO';

    public static function exigirLogin(): void
    {
        if (usuaria() === null) {
            redirecionar('login');
        }
        $limite = $GLOBALS['config']['sessao_minutos'] * 60;
        if (time() - (int)($_SESSION['ultimo_acesso'] ?? 0) > $limite) {
            self::encerrarSessao();
            session_start();
            avisar('aviso', 'Sua sessão expirou por falta de uso. Entre novamente.');
            redirecionar('login');
        }
        $_SESSION['ultimo_acesso'] = time();
    }

    public static function iniciarSessao(array $usuario): void
    {
        session_regenerate_id(true); // evita sequestro de sessão
        $_SESSION['usuaria'] = [
            'id' => (int)$usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
        ];
        $_SESSION['ultimo_acesso'] = time();
        unset($_SESSION['falhas'], $_SESSION['bloqueio_ate']);
    }

    public static function encerrarSessao(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Depois de 5 tentativas erradas, o login fica bloqueado por 5 minutos. */
    public static function bloqueado(): int
    {
        $ate = (int)($_SESSION['bloqueio_ate'] ?? 0);
        return max(0, $ate - time());
    }

    public static function registrarFalha(): void
    {
        $_SESSION['falhas'] = (int)($_SESSION['falhas'] ?? 0) + 1;
        if ($_SESSION['falhas'] >= 5) {
            $_SESSION['bloqueio_ate'] = time() + 300;
            $_SESSION['falhas'] = 0;
        }
    }
}
