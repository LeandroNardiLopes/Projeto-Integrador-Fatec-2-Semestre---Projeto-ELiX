<?php
/**
 * Usuario: a mentora, única usuária do sistema (perfil mentora_admin).
 */
declare(strict_types=1);

final class Usuario
{
    public static function porEmail(string $email): ?array
    {
        return Db::um('SELECT * FROM usuarios WHERE email = ? AND ativo = 1', [$email]);
    }

    public static function porId(int $id): ?array
    {
        return Db::um('SELECT id, nome, email, criado_em FROM usuarios WHERE id = ?', [$id]);
    }

    /** Primeiro acesso: a usuária do script ainda está com a senha provisória? */
    public static function aguardandoPrimeiraSenha(): ?array
    {
        return Db::um('SELECT id, nome, email FROM usuarios WHERE senha_hash = ? ORDER BY id LIMIT 1',
            [Auth::SENHA_PROVISORIA]);
    }

    public static function definirSenha(int $id, string $senha): void
    {
        Db::executar('UPDATE usuarios SET senha_hash = ? WHERE id = ?',
            [password_hash($senha, PASSWORD_DEFAULT), $id]);
    }

    public static function atualizarPerfil(int $id, string $nome, string $email): void
    {
        Db::executar('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?', [$nome, $email, $id]);
    }

    public static function senhaConfere(int $id, string $senha): bool
    {
        $u = Db::um('SELECT senha_hash FROM usuarios WHERE id = ?', [$id]);
        return $u !== null && password_verify($senha, $u['senha_hash']);
    }
}
