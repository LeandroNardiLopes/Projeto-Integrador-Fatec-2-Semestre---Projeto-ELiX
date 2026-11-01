<?php
/**
 * Painel da mentora: indicadores, alertas do dia, próximas aulas e estatísticas (RF30 a RF32).
 */
declare(strict_types=1);

final class PainelController
{
    public function index(): void
    {
        // RN08: antes de mostrar o painel, marca parcelas e atividades vencidas como atrasadas.
        Db::procedure('sp_atualizar_atrasos');

        render('painel/index', [
            'titulo'   => 'Painel',
            'resumo'   => Painel::resumo(),
            'hoje'     => Painel::aulasDeHoje(),
            'proximas' => Painel::proximas(8),
            'alertas'  => Painel::alertas(),
            'est'      => Painel::estatisticas(),
            'acoesTopo'=> '<a class="btn btn-destaque" href="' . e(url('agenda', 'novo')) . '">+ Agendar</a>'
                        . ' <a class="btn" href="' . e(url('alunos', 'novo')) . '">+ Aluno</a>',
        ]);
    }
}
