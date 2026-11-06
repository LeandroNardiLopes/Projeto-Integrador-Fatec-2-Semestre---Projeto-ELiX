<?php
/**
 * Minha conta: perfil e senha (RF02), registro de ações (RF03) e cópia de segurança (RF04).
 */
declare(strict_types=1);

final class ContaController
{
    public function index(): void
    {
        render('conta/index', [
            'titulo' => 'Minha conta',
            'u'      => Usuario::porId((int)usuaria()['id']),
        ]);
    }

    public function perfil(): void
    {
        exigir_post('conta');
        $nome = post('nome');
        $email = mb_strtolower(post('email'));
        if ($nome === '' || mb_strlen($nome) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            avisar('erro', 'Informe nome e um e-mail válido.');
            redirecionar('conta');
        }
        try {
            Usuario::atualizarPerfil((int)usuaria()['id'], $nome, $email);
        } catch (PDOException $e) {
            avisar('erro', Db::mensagemDeErro($e));
            redirecionar('conta');
        }
        $_SESSION['usuaria']['nome'] = $nome;
        $_SESSION['usuaria']['email'] = $email;
        avisar('ok', 'Dados atualizados.');
        redirecionar('conta');
    }

    public function senha(): void
    {
        exigir_post('conta');
        $id = (int)usuaria()['id'];
        $atual = (string)($_POST['atual'] ?? '');
        $nova = (string)($_POST['nova'] ?? '');
        $confirma = (string)($_POST['confirma'] ?? '');
        $erro = match (true) {
            !Usuario::senhaConfere($id, $atual) => 'A senha atual não confere.',
            mb_strlen($nova) < 8 => 'A nova senha precisa ter pelo menos 8 caracteres.',
            $nova !== $confirma => 'As duas senhas novas não são iguais.',
            default => null,
        };
        if ($erro !== null) {
            avisar('erro', $erro);
            redirecionar('conta');
        }
        Usuario::definirSenha($id, $nova);
        session_regenerate_id(true);
        registrar_log('senha_alterada');
        avisar('ok', 'Senha alterada.');
        redirecionar('conta');
    }

    public function logs(): void
    {
        render('conta/logs', [
            'titulo' => 'Registro de ações',
            'logs'   => Db::todos('SELECT * FROM logs_acesso ORDER BY id DESC LIMIT 300'),
        ]);
    }

    /** RF04: baixa a cópia de segurança. */
    public function backup(): void
    {
        exigir_post('conta');
        $sql = Backup::gerar();
        registrar_log('backup_gerado');
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="luvia-backup-' . date('Y-m-d-His') . '.sql"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    }

    /** RF04: restaura uma cópia de segurança (substitui todos os dados atuais). */
    public function restaurar(): void
    {
        exigir_post('conta');
        if (post('confirmo') !== 'SIM') {
            avisar('erro', 'Para restaurar, digite SIM no campo de confirmação.');
            redirecionar('conta');
        }
        $arq = $_FILES['arquivo'] ?? null;
        if (!is_array($arq) || ($arq['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($arq['tmp_name'])) {
            avisar('erro', 'Escolha o arquivo da cópia de segurança.');
            redirecionar('conta');
        }
        if ($arq['size'] > 20 * 1024 * 1024) {
            avisar('erro', 'O arquivo é grande demais (máximo de 20 MB).');
            redirecionar('conta');
        }
        try {
            $n = Backup::restaurar((string)file_get_contents($arq['tmp_name']));
        } catch (Throwable $t) {
            avisar('erro', $t instanceof RuntimeException ? $t->getMessage() : 'Não foi possível restaurar. Nada foi alterado.');
            redirecionar('conta');
        }
        // A sessão pode apontar para dados antigos: entra de novo.
        Auth::encerrarSessao();
        session_start();
        avisar('ok', "Cópia de segurança restaurada ({$n} registros). Entre novamente.");
        redirecionar('login');
    }
}
