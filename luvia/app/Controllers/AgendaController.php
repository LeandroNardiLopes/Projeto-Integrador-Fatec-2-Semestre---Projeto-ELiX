<?php
/**
 * Agenda semanal (RF13 a RF17) e frequência (RF20).
 */
declare(strict_types=1);

final class AgendaController
{
    /** RF17: agenda da semana (segunda a domingo). */
    public function index(): void
    {
        $ref = (string)($_GET['semana'] ?? '');
        $base = data_valida($ref) ? new DateTimeImmutable($ref) : new DateTimeImmutable('today');
        $segunda = $base->modify('-' . ((int)$base->format('N') - 1) . ' days');
        $domingo = $segunda->modify('+6 days');

        $itens = Agenda::periodo($segunda->format('Y-m-d'), $domingo->format('Y-m-d'));
        $porDia = [];
        foreach ($itens as $i) {
            $porDia[$i['data_aula']][] = $i;
        }
        render('agenda/index', [
            'titulo'   => 'Agenda',
            'segunda'  => $segunda,
            'porDia'   => $porDia,
            'total'    => count(array_filter($itens, fn($i) => $i['status'] !== 'cancelada')),
            'nobres'   => count(array_filter($itens, fn($i) => $i['status'] !== 'cancelada' && $i['horario_nobre'])),
            'acoesTopo'=> '<a class="btn btn-destaque" href="' . e(url('agenda', 'novo')) . '">+ Agendar</a>',
        ]);
    }

    public function novo(): void
    {
        $d = [
            'aluno_id' => get_int('aluno'),
            'lead_id'  => get_int('lead'),
            'tipo'     => get_int('lead') ? 'entrevista' : 'aula',
            'data'     => data_valida((string)($_GET['data'] ?? '')) ? $_GET['data'] : date('Y-m-d'),
            'inicio'   => '', 'fim' => '', 'observacao' => '',
        ];
        $this->formulario($d, []);
    }

    /** RF13: agenda aula (aluno) ou entrevista (lead). A procedure confere conflito e horário nobre. */
    public function salvar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirecionar('agenda');
        }
        $d = [
            'tipo' => post('tipo') === 'entrevista' ? 'entrevista' : 'aula',
            'aluno_id' => post_int('aluno_id'),
            'lead_id' => post_int('lead_id'),
            'data' => post('data'),
            'inicio' => post('inicio'),
            'fim' => post('fim'),
            'observacao' => post('observacao'),
        ];
        $erros = [];
        if ($d['tipo'] === 'aula' && $d['aluno_id'] === null) {
            $erros[] = 'Escolha o aluno da aula.';
        }
        if ($d['tipo'] === 'entrevista' && $d['lead_id'] === null) {
            $erros[] = 'Escolha o lead da entrevista.';
        }
        if (!data_valida($d['data'])) {
            $erros[] = 'Informe a data.';
        }
        if (!hora_valida($d['inicio']) || !hora_valida($d['fim'])) {
            $erros[] = 'Informe os horários de início e fim.';
        }
        if (mb_strlen($d['observacao']) > 500) {
            $erros[] = 'A observação passou de 500 caracteres.';
        }
        if ($erros) {
            $this->formulario($d, $erros);
            return;
        }
        try {
            Agenda::agendar(
                $d['tipo'] === 'aula' ? $d['aluno_id'] : null,
                $d['tipo'] === 'entrevista' ? $d['lead_id'] : null,
                $d['tipo'], $d['data'], $d['inicio'], $d['fim'], vazio_null($d['observacao'])
            );
        } catch (PDOException $e) {
            $this->formulario($d, [Db::mensagemDeErro($e)]);
            return;
        }
        avisar('ok', 'Agendado para ' . data_br($d['data']) . ' às ' . $d['inicio'] . '.');
        redirecionar('agenda', null, ['semana' => $d['data']]);
    }

    /** RF15/RN05: cancelamento com motivo. */
    public function cancelar(): void
    {
        exigir_post('agenda');
        $id = post_int('id');
        $motivo = post('motivo');
        try {
            Agenda::cancelar((int)$id, mb_substr($motivo, 0, 255));
            registrar_log('aula_cancelada', "Agendamento {$id}: {$motivo}");
            avisar('ok', 'Cancelado. O motivo ficou registrado.');
        } catch (PDOException $e) {
            avisar('erro', Db::mensagemDeErro($e));
        }
        voltar(url('agenda'));
    }

    /** RF20: presença ou falta. */
    public function frequencia(): void
    {
        exigir_post('agenda');
        $id = post_int('id');
        $presente = post('presente') === '1' ? 1 : 0;
        $ag = $id ? Agenda::porId($id) : null;
        if ($ag !== null && $ag['data_aula'] > date('Y-m-d')) {
            avisar('erro', 'Só é possível registrar presença ou falta no dia da aula ou depois.');
            voltar(url('agenda'));
        }
        try {
            Agenda::registrarFrequencia((int)$id, $presente);
            avisar('ok', $presente ? 'Presença registrada.' : 'Falta registrada. Você pode agendar a reposição.');
        } catch (PDOException $e) {
            avisar('erro', Db::mensagemDeErro($e));
        }
        voltar(url('agenda'));
    }

    /** RF16/RN06: reposição de uma falta. */
    public function reposicao(): void
    {
        exigir_post('agenda');
        $freq = post_int('frequencia_id');
        $data = post('data');
        $inicio = post('inicio');
        $fim = post('fim');
        if (!data_valida($data) || !hora_valida($inicio) || !hora_valida($fim)) {
            avisar('erro', 'Informe data, início e fim da reposição.');
            voltar(url('agenda'));
        }
        try {
            Agenda::reposicao((int)$freq, $data, $inicio, $fim);
            avisar('ok', 'Reposição agendada para ' . data_br($data) . ' às ' . $inicio . '.');
        } catch (PDOException $e) {
            avisar('erro', Db::mensagemDeErro($e));
        }
        voltar(url('agenda'));
    }

    private function formulario(array $d, array $erros): void
    {
        render('agenda/form', [
            'titulo' => 'Agendar',
            'd'      => $d,
            'erros'  => $erros,
            'alunos' => Aluno::ativosParaSelecao(),
            'leads'  => Db::todos('SELECT id, nome FROM leads WHERE status IN ("novo", "em_contato") ORDER BY nome'),
        ]);
    }
}
