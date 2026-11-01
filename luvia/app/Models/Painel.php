<?php
/**
 * Painel: indicadores, alertas do dia e estatísticas (RF30 a RF32).
 */
declare(strict_types=1);

final class Painel
{
    public static function resumo(): array
    {
        return Db::um('SELECT * FROM vw_resumo_dashboard') ?? [];
    }

    public static function proximas(int $limite = 8): array
    {
        return Db::todos('SELECT * FROM vw_proximas_aulas LIMIT ' . max(1, $limite));
    }

    public static function aulasDeHoje(): array
    {
        return Db::todos('SELECT * FROM vw_proximas_aulas WHERE data_aula = CURRENT_DATE');
    }

    /** RF32: alertas consolidados do dia. */
    public static function alertas(): array
    {
        return [
            'vencimentos' => Db::todos('SELECT * FROM vw_financeiro_pendencias
                                          WHERE vencimento <= CURRENT_DATE + INTERVAL 7 DAY ORDER BY vencimento'),
            'atividades'  => Db::todos('SELECT * FROM vw_atividades_pendentes WHERE status = "atrasada"'),
            'etapas'      => Db::todos('SELECT * FROM vw_cronograma_aluno WHERE situacao = "atrasada"'),
        ];
    }

    /** RF31: estatísticas dos últimos 3 meses. */
    public static function estatisticas(): array
    {
        $e = Db::um('SELECT
              (SELECT COUNT(*) FROM agendamentos WHERE status = "realizada" AND data_aula >= CURRENT_DATE - INTERVAL 3 MONTH) AS realizadas,
              (SELECT COUNT(*) FROM agendamentos WHERE status = "cancelada" AND data_aula >= CURRENT_DATE - INTERVAL 3 MONTH) AS canceladas,
              (SELECT COUNT(*) FROM agendamentos WHERE tipo = "reposicao" AND status = "realizada" AND data_aula >= CURRENT_DATE - INTERVAL 3 MONTH) AS repostas,
              (SELECT COUNT(*) FROM alunos WHERE data_inicio >= CURRENT_DATE - INTERVAL 3 MONTH) AS novos_alunos,
              (SELECT ROUND(100 * AVG(f.presente), 1) FROM frequencia f JOIN agendamentos ag ON ag.id = f.agendamento_id
                WHERE ag.data_aula >= CURRENT_DATE - INTERVAL 3 MONTH) AS frequencia_media') ?? [];
        $e['progresso'] = Db::todos('SELECT * FROM vw_progresso_aluno ORDER BY aluno');
        return $e;
    }
}
