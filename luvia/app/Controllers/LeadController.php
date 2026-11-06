<?php
/**
 * Leads e funil de acompanhamento (RF10 a RF12).
 */
declare(strict_types=1);

final class LeadController
{
    public function index(): void
    {
        $status = (string)($_GET['status'] ?? '');
        $busca = trim((string)($_GET['busca'] ?? ''));
        render('leads/index', [
            'titulo'   => 'Leads',
            'funil'    => Lead::funil(),
            'leads'    => Lead::listar($status, $busca),
            'filtro'   => compact('status', 'busca'),
            'acoesTopo'=> '<a class="btn btn-destaque" href="' . e(url('leads', 'novo')) . '">+ Novo lead</a>',
        ]);
    }

    public function novo(): void
    {
        $this->formulario(['status' => 'novo'], []);
    }

    public function salvar(): void
    {
        exigir_post('leads');
        [$d, $erros] = $this->ler();
        if ($erros) {
            $this->formulario($d, $erros);
            return;
        }
        Lead::criar($d);
        avisar('ok', 'Lead cadastrado.');
        redirecionar('leads');
    }

    public function editar(): void
    {
        $lead = $this->buscar(get_int('id'));
        $this->formulario($lead, []);
    }

    public function atualizar(): void
    {
        exigir_post('leads');
        $lead = $this->buscar(post_int('id'));
        [$d, $erros] = $this->ler();
        if ($lead['status'] === 'convertido') {
            $d['status'] = 'convertido';
        }
        if ($erros) {
            $this->formulario($d + $lead, $erros);
            return;
        }
        Lead::atualizar((int)$lead['id'], $d);
        avisar('ok', 'Lead atualizado.');
        redirecionar('leads');
    }

    /** Muda a etapa do funil (menos para "convertido", que só acontece pela conversão). */
    public function status(): void
    {
        exigir_post('leads');
        $lead = $this->buscar(post_int('id'));
        $status = post('status');
        if (in_array($status, ['novo', 'em_contato', 'desistiu'], true)) {
            Lead::mudarStatus((int)$lead['id'], $status);
            avisar('ok', 'Etapa do lead atualizada.');
        }
        redirecionar('leads');
    }

    /** RF12: converte em aluno e abre a ficha para montar o curso. */
    public function converter(): void
    {
        exigir_post('leads');
        $lead = $this->buscar(post_int('id'));
        try {
            $alunoId = Lead::converter((int)$lead['id']);
        } catch (PDOException $e) {
            avisar('erro', Db::mensagemDeErro($e));
            redirecionar('leads');
        }
        registrar_log('lead_convertido', $lead['nome']);
        avisar('ok', 'Lead convertido em aluno. Agora monte o curso dele.');
        redirecionar('alunos', 'ver', ['id' => $alunoId], 'cronograma');
    }

    private function ler(): array
    {
        $d = [
            'nome' => post('nome'),
            'email' => vazio_null(mb_strtolower(post('email'))),
            'telefone' => vazio_null(post('telefone')),
            'origem' => vazio_null(post('origem')),
            'nicho' => vazio_null(post('nicho')),
            'status' => post('status', 'novo'),
            'observacao' => vazio_null(post('observacao')),
        ];
        $erros = [];
        if ($d['nome'] === '' || mb_strlen($d['nome']) > 120) {
            $erros[] = 'Informe o nome (até 120 caracteres).';
        }
        if ($d['email'] !== null && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'O e-mail não é válido.';
        }
        if (!in_array($d['status'], ['novo', 'em_contato', 'desistiu', 'convertido'], true)) {
            $d['status'] = 'novo';
        }
        foreach (['telefone' => 30, 'origem' => 60, 'nicho' => 80, 'observacao' => 500] as $c => $max) {
            if ($d[$c] !== null && mb_strlen($d[$c]) > $max) {
                $erros[] = "O campo {$c} passou de {$max} caracteres.";
            }
        }
        return [$d, $erros];
    }

    private function formulario(array $lead, array $erros): void
    {
        render('leads/form', ['titulo' => empty($lead['id']) ? 'Novo lead' : 'Editar lead', 'lead' => $lead, 'erros' => $erros]);
    }

    private function buscar(?int $id): array
    {
        $lead = $id ? Lead::porId($id) : null;
        if ($lead === null) {
            avisar('erro', 'Lead não encontrado.');
            redirecionar('leads');
        }
        return $lead;
    }
}
