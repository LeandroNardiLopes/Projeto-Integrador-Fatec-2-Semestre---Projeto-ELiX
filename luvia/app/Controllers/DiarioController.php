<?php
/**
 * Diário de bordo da mentora (RF23).
 */
declare(strict_types=1);

final class DiarioController
{
    public function salvar(): void
    {
        exigir_post('alunos');
        $alunoId = (int)post_int('aluno_id');
        $data = post('data_registro');
        $texto = post('anotacao');
        if (Aluno::porId($alunoId) === null) {
            redirecionar('alunos');
        }
        if (!data_valida($data) || $texto === '' || mb_strlen($texto) > 5000) {
            avisar('erro', 'Informe a data e a anotação (até 5.000 caracteres).');
        } else {
            Diario::criar($alunoId, $data, $texto);
            avisar('ok', 'Anotação registrada.');
        }
        redirecionar('alunos', 'ver', ['id' => $alunoId], 'diario');
    }

    public function excluir(): void
    {
        exigir_post('alunos');
        $alunoId = Diario::excluir((int)post_int('id'));
        if ($alunoId === null) {
            redirecionar('alunos');
        }
        avisar('ok', 'Anotação excluída.');
        redirecionar('alunos', 'ver', ['id' => $alunoId], 'diario');
    }
}
