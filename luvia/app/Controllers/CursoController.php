<?php
/**
 * Cursos, módulos e lista semanal do curso (RF18).
 * RN11: mudar um curso não altera os cursos já montados para os alunos.
 */
declare(strict_types=1);

final class CursoController
{
    public function index(): void
    {
        render('cursos/index', [
            'titulo'   => 'Cursos',
            'cursos'   => Curso::todos(),
            'acoesTopo'=> '<a class="btn btn-destaque" href="' . e(url('cursos', 'novo')) . '">+ Novo curso</a>',
        ]);
    }

    public function novo(): void
    {
        $this->formulario(['modalidade' => 'online', 'aulas_online_semana' => 1, 'aulas_gravadas_semana' => 0, 'duracao_semanas' => 12, 'ativo' => 1], []);
    }

    public function salvar(): void
    {
        exigir_post('cursos');
        [$d, $erros] = $this->ler();
        if ($erros) {
            $this->formulario($this->paraForm($d), $erros);
            return;
        }
        try {
            $id = Curso::criar($d);
        } catch (PDOException $e) {
            $this->formulario($this->paraForm($d), [Db::mensagemDeErro($e)]);
            return;
        }
        avisar('ok', 'Curso criado. Agora cadastre os módulos e a lista de semanas.');
        redirecionar('cursos', 'ver', ['id' => $id]);
    }

    public function editar(): void
    {
        $this->formulario($this->buscar(get_int('id')), []);
    }

    public function atualizar(): void
    {
        exigir_post('cursos');
        $curso = $this->buscar(post_int('id'));
        [$d, $erros] = $this->ler();
        if ($erros) {
            $this->formulario(['id' => $curso['id']] + $this->paraForm($d), $erros);
            return;
        }
        try {
            Curso::atualizar((int)$curso['id'], $d);
        } catch (PDOException $e) {
            $this->formulario(['id' => $curso['id']] + $this->paraForm($d), [Db::mensagemDeErro($e)]);
            return;
        }
        avisar('ok', 'Curso atualizado. Os cursos já montados para os alunos não mudam.');
        redirecionar('cursos', 'ver', ['id' => $curso['id']]);
    }

    public function ver(): void
    {
        $curso = $this->buscar(get_int('id'));
        render('cursos/ver', [
            'titulo'  => $curso['nome'],
            'curso'   => $curso,
            'modulos' => Curso::modulos((int)$curso['id']),
            'aulas'   => Curso::aulasModelo((int)$curso['id']),
            'alunos'  => Aluno::listar('', 'ativos', '', (int)$curso['id']),
            'acoesTopo' => '<a class="btn" href="' . e(url('cursos', 'editar', ['id' => $curso['id']])) . '">Editar curso</a>',
        ]);
    }

    public function salvar_modulo(): void
    {
        exigir_post('cursos');
        $curso = $this->buscar(post_int('curso_id'));
        $d = [
            'ordem' => (int)post_int('ordem'), 'titulo' => post('titulo'), 'pilar' => post('pilar'),
            'ini' => (int)post_int('semana_inicio'), 'fim' => (int)post_int('semana_fim'), 'objetivo' => vazio_null(post('objetivo')),
        ];
        if ($d['titulo'] === '' || $d['pilar'] === '' || $d['ini'] < 1 || $d['fim'] < $d['ini']
            || $d['fim'] > (int)$curso['duracao_semanas'] || mb_strlen($d['titulo']) > 160 || mb_strlen($d['pilar']) > 40) {
            avisar('erro', 'Confira o módulo: título, pilar e semanas (a semana final não pode passar da duração do curso).');
        } else {
            try {
                Curso::salvarModulo((int)$curso['id'], post_int('id'), $d);
                avisar('ok', 'Módulo salvo.');
            } catch (PDOException $e) {
                avisar('erro', Db::mensagemDeErro($e));
            }
        }
        redirecionar('cursos', 'ver', ['id' => $curso['id']], 'modulos');
    }

    public function excluir_modulo(): void
    {
        exigir_post('cursos');
        $curso = $this->buscar(post_int('curso_id'));
        try {
            Curso::excluirModulo((int)post_int('id'));
            avisar('ok', 'Módulo excluído.');
        } catch (PDOException $e) {
            avisar('erro', 'Este módulo tem semanas cadastradas. Mude ou exclua as semanas antes.');
        }
        redirecionar('cursos', 'ver', ['id' => $curso['id']], 'modulos');
    }

    public function salvar_aula(): void
    {
        exigir_post('cursos');
        $curso = $this->buscar(post_int('curso_id'));
        $d = [
            'modulo_id' => (int)post_int('modulo_id'), 'semana' => (int)post_int('semana'), 'titulo' => post('titulo'),
            'estrutura' => vazio_null(post('estrutura')), 'tarefa' => vazio_null(post('tarefa')),
        ];
        $modulo = Db::um('SELECT * FROM curso_modulos WHERE id = ? AND curso_id = ?', [$d['modulo_id'], $curso['id']]);
        if ($modulo === null || $d['titulo'] === '' || $d['semana'] < 1 || $d['semana'] > (int)$curso['duracao_semanas']
            || mb_strlen($d['titulo']) > 160) {
            avisar('erro', 'Confira a semana: tema, módulo e número da semana (dentro da duração do curso).');
        } else {
            try {
                Curso::salvarAula((int)$curso['id'], post_int('id'), $d);
                avisar('ok', 'Semana salva na lista do curso.');
            } catch (PDOException $e) {
                avisar('erro', $e->errorInfo[1] === 1062 ? 'Já existe um tema para essa semana neste curso.' : Db::mensagemDeErro($e));
            }
        }
        redirecionar('cursos', 'ver', ['id' => $curso['id']], 'lista');
    }

    public function excluir_aula(): void
    {
        exigir_post('cursos');
        $curso = $this->buscar(post_int('curso_id'));
        Curso::excluirAula((int)post_int('id'));
        avisar('ok', 'Semana excluída da lista do curso.');
        redirecionar('cursos', 'ver', ['id' => $curso['id']], 'lista');
    }

    private function ler(): array
    {
        $d = [
            'nome' => post('nome'),
            'modalidade' => post('modalidade'),
            'online' => (int)post_int('aulas_online_semana'),
            'gravadas' => (int)post_int('aulas_gravadas_semana'),
            'semanas' => (int)post_int('duracao_semanas'),
            'descricao' => vazio_null(post('descricao')),
            'ativo' => post('ativo') === '1' ? 1 : 0,
        ];
        $erros = [];
        if ($d['nome'] === '' || mb_strlen($d['nome']) > 120) {
            $erros[] = 'Informe o nome do curso (até 120 caracteres).';
        }
        if (!isset(MODALIDADES[$d['modalidade']])) {
            $erros[] = 'Escolha a modalidade.';
        }
        if ($d['online'] > 7 || $d['gravadas'] > 7 || $d['online'] + $d['gravadas'] < 1) {
            $erros[] = 'O curso precisa ter pelo menos uma aula por semana (no máximo 7 de cada tipo).';
        }
        if ($d['semanas'] < 1 || $d['semanas'] > 52) {
            $erros[] = 'A duração precisa ficar entre 1 e 52 semanas.';
        }
        if ($d['descricao'] !== null && mb_strlen($d['descricao']) > 500) {
            $erros[] = 'A descrição passou de 500 caracteres.';
        }
        return [$d, $erros];
    }

    private function paraForm(array $d): array
    {
        return ['nome' => $d['nome'], 'modalidade' => $d['modalidade'], 'aulas_online_semana' => $d['online'],
                'aulas_gravadas_semana' => $d['gravadas'], 'duracao_semanas' => $d['semanas'],
                'descricao' => $d['descricao'], 'ativo' => $d['ativo']];
    }

    private function formulario(array $curso, array $erros): void
    {
        render('cursos/form', ['titulo' => empty($curso['id']) ? 'Novo curso' : 'Editar curso', 'curso' => $curso, 'erros' => $erros]);
    }

    private function buscar(?int $id): array
    {
        $c = $id ? Curso::porId($id) : null;
        if ($c === null) {
            avisar('erro', 'Curso não encontrado.');
            redirecionar('cursos');
        }
        return $c;
    }
}
