<?php
/**
 * Financeiro: cobranças, parcelas em série, baixa e pendências (RF26 a RF29).
 */
declare(strict_types=1);

final class FinanceiroController
{
    public function index(): void
    {
        Db::procedure('sp_atualizar_atrasos'); // RF28/RN08
        $alunoId = get_int('aluno');
        $status = (string)($_GET['status'] ?? '');
        $mes = (string)($_GET['mes'] ?? '');
        render('financeiro/index', [
            'titulo'  => 'Financeiro',
            'totais'  => Financeiro::totais(),
            'parcelas'=> Financeiro::listar($alunoId, $status, $mes),
            'alunos'  => Db::todos('SELECT id, nome FROM alunos ORDER BY ativo DESC, nome'),
            'filtro'  => compact('alunoId', 'status', 'mes'),
        ]);
    }

    /** RF26: uma parcela avulsa. */
    public function salvar(): void
    {
        exigir_post('financeiro');
        [$alunoId, $descricao, $valor, $venc] = [post_int('aluno_id'), post('descricao'), $this->valor(post('valor')), post('vencimento')];
        if ($alunoId === null || Aluno::porId($alunoId) === null || $descricao === '' || mb_strlen($descricao) > 120
            || $valor === null || !data_valida($venc)) {
            avisar('erro', 'Informe aluno, descrição, valor e vencimento.');
        } else {
            Financeiro::criar($alunoId, $descricao, $valor, $venc);
            avisar('ok', 'Cobrança registrada.');
        }
        voltar(url('financeiro'));
    }

    /** RF26: várias parcelas mensais de uma vez. */
    public function gerar(): void
    {
        exigir_post('financeiro');
        $alunoId = post_int('aluno_id');
        $descricao = post('descricao', 'Parcela');
        $valor = $this->valor(post('valor'));
        $qtd = (int)post_int('quantidade');
        $primeiro = post('primeiro_vencimento');
        if ($alunoId === null || Aluno::porId($alunoId) === null || $valor === null || $qtd < 1 || $qtd > 24
            || !data_valida($primeiro) || $descricao === '' || mb_strlen($descricao) > 100) {
            avisar('erro', 'Informe aluno, valor, quantidade (1 a 24) e o primeiro vencimento.');
        } else {
            Financeiro::gerarSerie($alunoId, $descricao, $valor, $qtd, $primeiro);
            avisar('ok', "{$qtd} parcela(s) gerada(s).");
        }
        voltar(url('financeiro'));
    }

    /** RF27: baixa com data e forma de pagamento. */
    public function pagar(): void
    {
        exigir_post('financeiro');
        $id = (int)post_int('id');
        $data = post('data_pagamento');
        $forma = post('forma_pagamento');
        if (!data_valida($data) || !in_array($forma, FORMAS_PAGAMENTO, true)) {
            avisar('erro', 'Informe a data e a forma de pagamento.');
            voltar(url('financeiro'));
        }
        try {
            Financeiro::pagar($id, $data, $forma);
            $p = Financeiro::porId($id);
            registrar_log('pagamento_registrado', ($p['descricao'] ?? '') . ' · ' . moeda($p['valor'] ?? 0));
            avisar('ok', 'Pagamento registrado.');
        } catch (PDOException $e) {
            avisar('erro', Db::mensagemDeErro($e));
        }
        voltar(url('financeiro'));
    }

    /** Exclui uma cobrança lançada por engano (parcelas pagas não podem ser excluídas). */
    public function excluir(): void
    {
        exigir_post('financeiro');
        Financeiro::excluir((int)post_int('id'));
        avisar('ok', 'Cobrança excluída (parcelas já pagas são mantidas).');
        voltar(url('financeiro'));
    }

    /** Aceita "1.234,56", "1234,56" ou "1234.56". */
    private function valor(string $v): ?float
    {
        $v = str_replace(['R$', ' '], '', $v);
        if (str_contains($v, ',')) {
            $v = str_replace(['.', ','], ['', '.'], $v);
        }
        if (!is_numeric($v) || (float)$v <= 0 || (float)$v > 1000000) {
            return null;
        }
        return round((float)$v, 2);
    }
}
