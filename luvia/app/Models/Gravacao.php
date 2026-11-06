<?php
/**
 * Gravacao: links das aulas gravadas, gerais ou de um aluno (RF25, RN10).
 */
declare(strict_types=1);

final class Gravacao
{
    public static function todas(?int $alunoId = null): array
    {
        $sql = 'SELECT g.*, a.nome AS aluno FROM aulas_gravadas g LEFT JOIN alunos a ON a.id = g.aluno_id';
        $p = [];
        if ($alunoId !== null) {
            $sql .= ' WHERE g.aluno_id = ?';
            $p[] = $alunoId;
        }
        return Db::todos($sql . ' ORDER BY g.data_aula DESC, g.id DESC', $p);
    }

    public static function doAluno(int $alunoId): array
    {
        return Db::todos('SELECT * FROM aulas_gravadas WHERE aluno_id = ? OR aluno_id IS NULL ORDER BY data_aula DESC, id DESC', [$alunoId]);
    }

    public static function criar(?int $alunoId, string $titulo, string $link, string $data): void
    {
        Db::executar('INSERT INTO aulas_gravadas (aluno_id, titulo, link, data_aula) VALUES (?, ?, ?, ?)', [$alunoId, $titulo, $link, $data]);
    }

    public static function excluir(int $id): void
    {
        Db::executar('DELETE FROM aulas_gravadas WHERE id = ?', [$id]);
    }
}
