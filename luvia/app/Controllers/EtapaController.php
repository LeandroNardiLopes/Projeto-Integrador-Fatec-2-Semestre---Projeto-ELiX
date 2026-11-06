<?php
/**
 * Etapas do curso de cada aluno: montar o curso (RF19), concluir semanas (RF21) e personalizar.
 */
declare(strict_types=1);

final class EtapaController
{
    public function aplicar(): void
    {
        exigir_post('alunos');
        $alunoId = (int)post_int('aluno_id');
        $cursoId = (int)post_int('curso_id');
        $inicio = post('data_inicio');
        if (!data_valida($inicio)) {
            avisar('erro', 'Informe a data de início do curso.');
            redirecionar('alunos', 'ver', ['id' => $alunoId], 'cronograma');
        }
        try {
            Etapa::aplicarCurso($alunoId, $cursoId, $inicio);
            avisar('ok', 'Curso montado com as datas de cada semana.');
        } catch (PDOException $e) {
            avisar('erro', Db::mensagemDeErro($e));
        }
        redirecionar('alunos', 'ver', ['id' => $alunoId], 'cronograma');
    }

    public function concluir(): void
    {
        $et = $this->etapaDoPost();
        Etapa::concluir((int)$et['id']);
        avisar('ok', 'Semana concluída.');
        redirecionar('alunos', 'ver', ['id' => $et['aluno_id']], 'cronograma');
    }

    public function reabrir(): void
    {
        $et = $this->etapaDoPost();
        Etapa::reabrir((int)$et['id']);
        avisar('ok', 'Semana reaberta.');
        redirecionar('alunos', 'ver', ['id' => $et['aluno_id']], 'cronograma');
    }

    /** Cria (sem id) ou edita (com id) uma etapa. */
    public function salvar(): void
    {
        exigir_post('alunos');
        $alunoId = (int)post_int('aluno_id');
        $titulo = post('titulo');
        $data = vazio_null(post('data_prevista'));
        if ($titulo === '' || mb_strlen($titulo) > 160 || ($data !== null && !data_valida($data))) {
            avisar('erro', 'Informe o tema (até 160 caracteres) e uma data válida.');
            redirecionar('alunos', 'ver', ['id' => $alunoId], 'cronograma');
        }
        $id = post_int('id');
        if ($id !== null) {
            $et = Etapa::porId($id);
            if ($et === null || (int)$et['aluno_id'] !== $alunoId) {
                redirecionar('alunos');
            }
            Etapa::atualizar($id, $titulo, $data);
            avisar('ok', 'Etapa atualizada.');
        } else {
            if (Aluno::porId($alunoId) === null) {
                redirecionar('alunos');
            }
            Etapa::criar($alunoId, $titulo, $data);
            avisar('ok', 'Etapa adicionada ao curso do aluno.');
        }
        redirecionar('alunos', 'ver', ['id' => $alunoId], 'cronograma');
    }

    public function excluir(): void
    {
        $et = $this->etapaDoPost();
        Etapa::excluir((int)$et['id']);
        avisar('ok', 'Etapa excluída.');
        redirecionar('alunos', 'ver', ['id' => $et['aluno_id']], 'cronograma');
    }

    public function mover(): void
    {
        $et = $this->etapaDoPost();
        Etapa::mover((int)$et['id'], post('direcao') === 'subir' ? 'subir' : 'descer');
        redirecionar('alunos', 'ver', ['id' => $et['aluno_id']], 'cronograma');
    }

    private function etapaDoPost(): array
    {
        exigir_post('alunos');
        $id = post_int('id');
        $et = $id ? Etapa::porId($id) : null;
        if ($et === null) {
            avisar('erro', 'Etapa não encontrada.');
            redirecionar('alunos');
        }
        return $et;
    }
}
