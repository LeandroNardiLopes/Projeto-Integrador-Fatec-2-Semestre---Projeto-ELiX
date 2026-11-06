<?php
/**
 * Backup: cópia de segurança dos dados e restauração (RF04).
 * Como o sistema roda só no computador da mentora, a cópia evita perder tudo
 * se o computador der problema. O arquivo gerado tem só os DADOS; a estrutura
 * (tabelas, views e procedures) vem do arquivo database/luvia_mysql.sql.
 */
declare(strict_types=1);

final class Backup
{
    public const CABECALHO = '-- LUVIA BACKUP';

    /** Ordem que respeita as ligações entre as tabelas. */
    public const TABELAS = [
        'usuarios', 'cursos', 'curso_modulos', 'curso_aulas_modelo', 'alunos', 'leads', 'materiais',
        'aulas_gravadas', 'etapas_curso', 'agendamentos', 'frequencia', 'atividades', 'financeiro',
        'diario_bordo', 'logs_acesso',
    ];

    public static function gerar(): string
    {
        $pdo = Db::conexao();
        $saida = [self::CABECALHO, '-- gerado em ' . date('d/m/Y H:i'), 'SET FOREIGN_KEY_CHECKS=0;'];
        foreach (array_reverse(self::TABELAS) as $t) {
            $saida[] = "DELETE FROM `{$t}`;";
        }
        foreach (self::TABELAS as $t) {
            foreach (Db::todos("SELECT * FROM `{$t}` ORDER BY id") as $linha) {
                $colunas = '`' . implode('`, `', array_keys($linha)) . '`';
                $valores = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($linha));
                $saida[] = "INSERT INTO `{$t}` ({$colunas}) VALUES (" . implode(', ', $valores) . ');';
            }
        }
        $saida[] = 'SET FOREIGN_KEY_CHECKS=1;';
        return implode("\n", $saida) . "\n";
    }

    /**
     * Restaura um arquivo gerado por gerar(). Só aceita os comandos que o próprio
     * backup produz (proteção contra arquivos alterados). Devolve quantos registros voltaram.
     */
    public static function restaurar(string $conteudo): int
    {
        $linhas = preg_split('/\r?\n/', trim($conteudo));
        if (($linhas[0] ?? '') !== self::CABECALHO) {
            throw new RuntimeException('Este arquivo não é uma cópia de segurança do Luvia.');
        }
        $tabelas = implode('|', self::TABELAS);
        $padroes = [
            '/^SET FOREIGN_KEY_CHECKS=[01];$/',
            "/^DELETE FROM `({$tabelas})`;$/",
            "/^INSERT INTO `({$tabelas})` \\(`[a-z_]+`(, `[a-z_]+`)*\\) VALUES \\(.*\\);$/",
        ];
        $comandos = [];
        foreach ($linhas as $l) {
            if ($l === '' || str_starts_with($l, '--')) {
                continue;
            }
            $valida = false;
            foreach ($padroes as $p) {
                if (preg_match($p, $l)) {
                    $valida = true;
                    break;
                }
            }
            if (!$valida) {
                throw new RuntimeException('O arquivo tem um comando inesperado e não foi usado.');
            }
            $comandos[] = $l;
        }

        $pdo = Db::conexao();
        $inseridos = 0;
        $pdo->beginTransaction();
        try {
            foreach ($comandos as $c) {
                $pdo->exec($c);
                if (str_starts_with($c, 'INSERT')) {
                    $inseridos++;
                }
            }
            $pdo->commit();
        } catch (Throwable $t) {
            $pdo->rollBack();
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            throw $t;
        }
        return $inseridos;
    }
}
