<?php
/**
 * Diario: anotações da mentora sobre cada aluno (RF23).
 */
declare(strict_types=1);

final class Diario
{
    public static function doAluno(int $alunoId): array
    {
        return Db::todos('SELECT * FROM diario_bordo WHERE aluno_id = ? ORDER BY data_registro DESC, id DESC', [$alunoId]);
    }

    public static function criar(int $alunoId, string $data, string $anotacao): void
    {
        Db::executar('INSERT INTO diario_bordo (aluno_id, data_registro, anotacao) VALUES (?, ?, ?)', [$alunoId, $data, $anotacao]);
    }

    public static function excluir(int $id): ?int
    {
        $d = Db::um('SELECT aluno_id FROM diario_bordo WHERE id = ?', [$id]);
        if ($d === null) {
            return null;
        }
        Db::executar('DELETE FROM diario_bordo WHERE id = ?', [$id]);
        return (int)$d['aluno_id'];
    }
}
