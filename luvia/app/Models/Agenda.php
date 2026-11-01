<?php
/**
 * Agenda: aulas, entrevistas, cancelamentos, frequência e reposições (RF13 a RF17, RF20).
 * As regras (conflito, horário nobre, motivo obrigatório, reposição) ficam nas procedures.
 */
declare(strict_types=1);

final class Agenda
{
    public static function periodo(string $inicio, string $fim): array
    {
        return Db::todos('SELECT ag.*, COALESCE(a.nome, l.nome) AS pessoa,
                                 CASE WHEN ag.aluno_id IS NOT NULL THEN "aluno" ELSE "lead" END AS vinculo,
                                 f.id AS frequencia_id, f.presente, f.reposta, f.reposicao_agendamento_id
                            FROM agendamentos ag
                       LEFT JOIN alunos a ON a.id = ag.aluno_id
                       LEFT JOIN leads l ON l.id = ag.lead_id
                       LEFT JOIN frequencia f ON f.agendamento_id = ag.id
                           WHERE ag.data_aula BETWEEN ? AND ?
                        ORDER BY ag.data_aula, ag.hora_inicio', [$inicio, $fim]);
    }

    public static function porId(int $id): ?array
    {
        return Db::um('SELECT * FROM agendamentos WHERE id = ?', [$id]);
    }

    /** RF13/RF14/RN04: agenda com verificação de conflito e cálculo de horário nobre. Devolve o id. */
    public static function agendar(?int $alunoId, ?int $leadId, string $tipo, string $data, string $inicio, string $fim, ?string $obs): int
    {
        $r = Db::procedure('sp_agendar_aula', [$alunoId, $leadId, $tipo, $data, $inicio, $fim, $obs], ['@novo_agendamento']);
        return (int)$r['@novo_agendamento'];
    }

    /** RF15/RN05: cancela com motivo obrigatório. */
    public static function cancelar(int $id, string $motivo): void
    {
        Db::procedure('sp_cancelar_aula', [$id, $motivo]);
    }

    /** RF20: presença (1) ou falta (0). */
    public static function registrarFrequencia(int $id, int $presente): void
    {
        Db::procedure('sp_registrar_frequencia', [$id, $presente]);
    }

    /** RF16/RN06: reposição de uma falta. Devolve o id da nova aula. */
    public static function reposicao(int $frequenciaId, string $data, string $inicio, string $fim): int
    {
        $r = Db::procedure('sp_registrar_reposicao', [$frequenciaId, $data, $inicio, $fim], ['@nova_reposicao']);
        return (int)$r['@nova_reposicao'];
    }
}
