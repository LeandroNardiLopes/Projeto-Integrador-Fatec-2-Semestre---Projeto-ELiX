<?php
/**
 * Aluno: cadastro, busca, inativação e dados da ficha (RF05 a RF09).
 * Os alunos não fazem login: são só registros que a mentora gerencia.
 */
declare(strict_types=1);

final class Aluno
{
    public static function listar(string $busca = '', string $situacao = 'ativos', string $nicho = '', ?int $cursoId = null): array
    {
        $sql = 'SELECT a.*, c.nome AS curso,
                       (SELECT COUNT(*) FROM etapas_curso e WHERE e.aluno_id = a.id) AS total_etapas,
                       (SELECT COUNT(*) FROM etapas_curso e WHERE e.aluno_id = a.id AND e.status = "concluida") AS etapas_concluidas,
                       (SELECT MIN(CONCAT(ag.data_aula, " ", ag.hora_inicio)) FROM agendamentos ag
                         WHERE ag.aluno_id = a.id AND ag.status = "agendada" AND ag.data_aula >= CURRENT_DATE) AS proxima_aula
                  FROM alunos a
             LEFT JOIN cursos c ON c.id = a.curso_id
                 WHERE 1 = 1';
        $p = [];
        if ($busca !== '') {
            $sql .= ' AND (a.nome LIKE ? OR a.email LIKE ? OR a.nicho LIKE ?)';
            $like = '%' . $busca . '%';
            array_push($p, $like, $like, $like);
        }
        if ($situacao === 'ativos') {
            $sql .= ' AND a.ativo = 1';
        } elseif ($situacao === 'inativos') {
            $sql .= ' AND a.ativo = 0';
        }
        if ($nicho !== '') {
            $sql .= ' AND a.nicho = ?';
            $p[] = $nicho;
        }
        if ($cursoId !== null) {
            $sql .= ' AND a.curso_id = ?';
            $p[] = $cursoId;
        }
        return Db::todos($sql . ' ORDER BY a.ativo DESC, a.nome', $p);
    }

    public static function porId(int $id): ?array
    {
        return Db::um('SELECT a.*, c.nome AS curso, c.modalidade, c.aulas_online_semana, c.aulas_gravadas_semana
                         FROM alunos a LEFT JOIN cursos c ON c.id = a.curso_id WHERE a.id = ?', [$id]);
    }

    public static function nichos(): array
    {
        return array_column(Db::todos('SELECT DISTINCT nicho FROM alunos WHERE nicho IS NOT NULL AND nicho <> "" ORDER BY nicho'), 'nicho');
    }

    /** Alunos ativos para listas de seleção (agenda, financeiro, materiais). */
    public static function ativosParaSelecao(): array
    {
        return Db::todos('SELECT id, nome FROM alunos WHERE ativo = 1 ORDER BY nome');
    }

    public static function criar(array $d): int
    {
        Db::executar('INSERT INTO alunos (nome, email, telefone, nicho, data_inicio, contrato_link,
                                          nivel_inicial, nivel_alvo, cena_objetivo, data_cena)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $d['nome'], $d['email'], $d['telefone'], $d['nicho'], $d['data_inicio'], $d['contrato_link'],
            $d['nivel_inicial'], $d['nivel_alvo'], $d['cena_objetivo'], $d['data_cena'],
        ]);
        return Db::ultimoId();
    }

    public static function atualizar(int $id, array $d): void
    {
        Db::executar('UPDATE alunos SET nome = ?, email = ?, telefone = ?, nicho = ?, data_inicio = ?, contrato_link = ?,
                             nivel_inicial = ?, nivel_alvo = ?, cena_objetivo = ?, data_cena = ?
                       WHERE id = ?', [
            $d['nome'], $d['email'], $d['telefone'], $d['nicho'], $d['data_inicio'], $d['contrato_link'],
            $d['nivel_inicial'], $d['nivel_alvo'], $d['cena_objetivo'], $d['data_cena'], $id,
        ]);
    }

    /** RN07: inativa sem apagar o histórico e cancela as aulas futuras (procedure). */
    public static function inativar(int $id, string $motivo): void
    {
        Db::procedure('sp_inativar_aluno', [$id, $motivo]);
    }

    public static function reativar(int $id): void
    {
        Db::executar('UPDATE alunos SET ativo = 1, data_inativacao = NULL, motivo_inativacao = NULL WHERE id = ?', [$id]);
    }

    /** Frequência do aluno (mesma regra da view vw_frequencia_aluno, vale também para inativos). */
    public static function frequencia(int $id): array
    {
        return Db::um('SELECT COUNT(f.id) AS aulas_registradas,
                              COALESCE(SUM(f.presente = 1), 0) AS presencas,
                              COALESCE(SUM(f.presente = 0), 0) AS faltas,
                              COALESCE(SUM(f.presente = 0 AND f.reposta = 1), 0) AS faltas_repostas,
                              ROUND(100 * COALESCE(SUM(f.presente = 1), 0) / NULLIF(COUNT(f.id), 0), 1) AS percentual_frequencia
                         FROM frequencia f WHERE f.aluno_id = ?', [$id]) ?? [];
    }

    /** Aulas do aluno com a frequência de cada uma. */
    public static function aulas(int $id): array
    {
        return Db::todos('SELECT ag.*, f.id AS frequencia_id, f.presente, f.reposta, f.reposicao_agendamento_id
                            FROM agendamentos ag
                       LEFT JOIN frequencia f ON f.agendamento_id = ag.id
                           WHERE ag.aluno_id = ?
                        ORDER BY ag.data_aula DESC, ag.hora_inicio DESC', [$id]);
    }
}
