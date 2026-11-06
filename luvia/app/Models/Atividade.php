<?php
/**
 * Atividade: tarefas propostas ao aluno, com prazo e status (RF22).
 */
declare(strict_types=1);

final class Atividade
{
    public const STATUS = ['pendente' => 'Pendente', 'entregue' => 'Entregue', 'atrasada' => 'Atrasada'];

    public static function doAluno(int $alunoId): array
    {
        return Db::todos('SELECT * FROM atividades WHERE aluno_id = ?
                        ORDER BY FIELD(status, "atrasada", "pendente", "entregue"), data_entrega', [$alunoId]);
    }

    public static function criar(int $alunoId, string $titulo, ?string $descricao, ?string $prazo): void
    {
        $status = ($prazo !== null && $prazo < date('Y-m-d')) ? 'atrasada' : 'pendente';
        Db::executar('INSERT INTO atividades (aluno_id, titulo, descricao, data_entrega, status) VALUES (?, ?, ?, ?, ?)',
            [$alunoId, $titulo, $descricao, $prazo, $status]);
    }

    public static function mudarStatus(int $id, string $status): ?int
    {
        $a = Db::um('SELECT aluno_id FROM atividades WHERE id = ?', [$id]);
        if ($a === null) {
            return null;
        }
        Db::executar('UPDATE atividades SET status = ? WHERE id = ?', [$status, $id]);
        return (int)$a['aluno_id'];
    }
}
