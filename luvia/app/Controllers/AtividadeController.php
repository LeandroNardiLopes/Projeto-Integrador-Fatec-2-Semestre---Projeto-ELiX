<?php
/**
 * Atividades propostas ao aluno (RF22).
 */
declare(strict_types=1);

final class AtividadeController
{
    public function salvar(): void
    {
        exigir_post('alunos');
        $alunoId = (int)post_int('aluno_id');
        $titulo = post('titulo');
        $descricao = vazio_null(post('descricao'));
        $prazo = vazio_null(post('data_entrega'));
        if (Aluno::porId($alunoId) === null) {
            redirecionar('alunos');
        }
        if ($titulo === '' || mb_strlen($titulo) > 160 || ($descricao !== null && mb_strlen($descricao) > 500)
            || ($prazo !== null && !data_valida($prazo))) {
            avisar('erro', 'Informe o título (até 160 caracteres), a descrição (até 500) e um prazo válido.');
        } else {
            Atividade::criar($alunoId, $titulo, $descricao, $prazo);
            avisar('ok', 'Atividade cadastrada.');
        }
        redirecionar('alunos', 'ver', ['id' => $alunoId], 'atividades');
    }

    public function status(): void
    {
        exigir_post('alunos');
        $status = post('status');
        if (!isset(Atividade::STATUS[$status])) {
            redirecionar('alunos');
        }
        $alunoId = Atividade::mudarStatus((int)post_int('id'), $status);
        if ($alunoId === null) {
            redirecionar('alunos');
        }
        avisar('ok', 'Status da atividade atualizado.');
        redirecionar('alunos', 'ver', ['id' => $alunoId], 'atividades');
    }
}
