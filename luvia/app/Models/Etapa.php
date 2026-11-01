<?php
/**
 * Etapa: o curso montado para cada aluno, semana a semana (RF19 e RF21).
 */
declare(strict_types=1);

final class Etapa
{
    /** Cronograma do aluno com a situação de cada semana (mesma regra da vw_cronograma_aluno). */
    public static function doAluno(int $alunoId): array
    {
        return Db::todos('SELECT e.*,
                                 e.data_prevista + INTERVAL 6 DAY AS fim_semana,
                                 CASE
                                   WHEN e.status = "concluida" THEN "concluída"
                                   WHEN e.data_prevista IS NULL THEN "sem data"
                                   WHEN CURRENT_DATE BETWEEN e.data_prevista AND e.data_prevista + INTERVAL 6 DAY THEN "semana atual"
                                   WHEN e.data_prevista < CURRENT_DATE THEN "atrasada"
                                   ELSE "prevista"
                                 END AS situacao
                            FROM etapas_curso e
                           WHERE e.aluno_id = ?
                        ORDER BY e.ordem, e.id', [$alunoId]);
    }

    public static function porId(int $id): ?array
    {
        return Db::um('SELECT * FROM etapas_curso WHERE id = ?', [$id]);
    }

    /** Copia a lista do curso para o aluno (procedure sp_aplicar_curso). */
    public static function aplicarCurso(int $alunoId, int $cursoId, string $dataInicio): void
    {
        Db::procedure('sp_aplicar_curso', [$alunoId, $cursoId, $dataInicio]);
    }

    public static function concluir(int $id): void
    {
        Db::procedure('sp_concluir_etapa', [$id]);
    }

    public static function reabrir(int $id): void
    {
        Db::executar('UPDATE etapas_curso SET status = "pendente", data_conclusao = NULL WHERE id = ?', [$id]);
    }

    public static function criar(int $alunoId, string $titulo, ?string $dataPrevista): void
    {
        $prox = Db::um('SELECT COALESCE(MAX(ordem), 0) + 1 AS o, COALESCE(MAX(semana), 0) + 1 AS s FROM etapas_curso WHERE aluno_id = ?', [$alunoId]);
        Db::executar('INSERT INTO etapas_curso (aluno_id, titulo, ordem, semana, modulo, data_prevista) VALUES (?, ?, ?, ?, ?, ?)',
            [$alunoId, $titulo, $prox['o'], $prox['s'], 'Etapa personalizada', $dataPrevista]);
    }

    public static function atualizar(int $id, string $titulo, ?string $dataPrevista): void
    {
        Db::executar('UPDATE etapas_curso SET titulo = ?, data_prevista = ? WHERE id = ?', [$titulo, $dataPrevista, $id]);
    }

    public static function excluir(int $id): void
    {
        Db::executar('DELETE FROM etapas_curso WHERE id = ?', [$id]);
    }

    /** Troca a ordem com a etapa vizinha (subir ou descer). */
    public static function mover(int $id, string $direcao): void
    {
        $e = self::porId($id);
        if ($e === null) {
            return;
        }
        $vizinha = $direcao === 'subir'
            ? Db::um('SELECT id, ordem FROM etapas_curso WHERE aluno_id = ? AND (ordem < ? OR (ordem = ? AND id < ?)) ORDER BY ordem DESC, id DESC LIMIT 1',
                [$e['aluno_id'], $e['ordem'], $e['ordem'], $e['id']])
            : Db::um('SELECT id, ordem FROM etapas_curso WHERE aluno_id = ? AND (ordem > ? OR (ordem = ? AND id > ?)) ORDER BY ordem, id LIMIT 1',
                [$e['aluno_id'], $e['ordem'], $e['ordem'], $e['id']]);
        if ($vizinha === null) {
            return;
        }
        $pdo = Db::conexao();
        $pdo->beginTransaction();
        $ordemVizinha = (int)$vizinha['ordem'] === (int)$e['ordem']
            ? ((int)$e['ordem'] + ($direcao === 'subir' ? -1 : 1)) : (int)$vizinha['ordem'];
        Db::executar('UPDATE etapas_curso SET ordem = ? WHERE id = ?', [$e['ordem'], $vizinha['id']]);
        Db::executar('UPDATE etapas_curso SET ordem = ? WHERE id = ?', [$ordemVizinha, $e['id']]);
        $pdo->commit();
    }
}
