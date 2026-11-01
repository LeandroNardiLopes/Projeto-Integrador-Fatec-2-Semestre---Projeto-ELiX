<?php
/**
 * Alunos e contratos (RF05 a RF09) e ficha do aluno com histórico completo (RF08).
 */
declare(strict_types=1);

final class AlunoController
{
    public function index(): void
    {
        $busca = trim((string)($_GET['busca'] ?? ''));
        $situacao = in_array($_GET['situacao'] ?? 'ativos', ['ativos', 'inativos', 'todos'], true) ? $_GET['situacao'] ?? 'ativos' : 'ativos';
        $nicho = trim((string)($_GET['nicho'] ?? ''));
        $cursoId = get_int('curso');

        render('alunos/index', [
            'titulo'   => 'Alunos',
            'alunos'   => Aluno::listar($busca, $situacao, $nicho, $cursoId),
            'nichos'   => Aluno::nichos(),
            'cursos'   => Curso::ativos(),
            'filtro'   => compact('busca', 'situacao', 'nicho', 'cursoId'),
            'acoesTopo'=> '<a class="btn btn-destaque" href="' . e(url('alunos', 'novo')) . '">+ Novo aluno</a>',
        ]);
    }

    public function novo(): void
    {
        $this->formulario(['data_inicio' => date('Y-m-d')], []);
    }

    public function salvar(): void
    {
        $this->exigirPost();
        [$d, $erros] = $this->lerFormulario(true);
        if ($erros) {
            $this->formulario($d, $erros);
            return;
        }
        $pdo = Db::conexao();
        try {
            $pdo->beginTransaction();
            $id = Aluno::criar($d);
            if ($d['curso_id'] !== null) {
                Etapa::aplicarCurso($id, $d['curso_id'], $d['data_inicio']);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->formulario($d, [Db::mensagemDeErro($e)]);
            return;
        }
        registrar_log('aluno_cadastrado', $d['nome']);
        avisar('ok', 'Aluno cadastrado.' . ($d['curso_id'] !== null ? ' O curso foi montado com as datas de cada semana.' : ''));
        redirecionar('alunos', 'ver', ['id' => $id]);
    }

    public function editar(): void
    {
        $aluno = $this->buscar(get_int('id'));
        $this->formulario($aluno, []);
    }

    public function atualizar(): void
    {
        $this->exigirPost();
        $aluno = $this->buscar(post_int('id'));
        [$d, $erros] = $this->lerFormulario(false);
        $d['id'] = $aluno['id'];
        if ($erros) {
            $this->formulario($d + $aluno, $erros);
            return;
        }
        Aluno::atualizar((int)$aluno['id'], $d);
        registrar_log('aluno_editado', $d['nome']);
        avisar('ok', 'Dados do aluno atualizados.');
        redirecionar('alunos', 'ver', ['id' => $aluno['id']]);
    }

    /** RF08: ficha com o histórico completo do aluno. */
    public function ver(): void
    {
        $aluno = $this->buscar(get_int('id'));
        $id = (int)$aluno['id'];
        $dados = [
            'titulo'     => $aluno['nome'],
            'aluno'      => $aluno,
            'frequencia' => Aluno::frequencia($id),
            'etapas'     => Etapa::doAluno($id),
            'aulas'      => Aluno::aulas($id),
            'cursos'     => Curso::ativos(),
        ];
        // Seções que só existem na versão completa do sistema.
        if (modulo_ativo('atividades')) {
            $dados['atividades'] = Atividade::doAluno($id);
        }
        if (modulo_ativo('financeiro')) {
            $dados['parcelas'] = Financeiro::doAluno($id);
        }
        if (modulo_ativo('materiais')) {
            $dados['materiais'] = Material::doAluno($id);
            $dados['gravacoes'] = Gravacao::doAluno($id);
        }
        if (modulo_ativo('diario')) {
            $dados['diario'] = Diario::doAluno($id);
        }
        $dados['acoesTopo'] = '<a class="btn" href="' . e(url('alunos', 'editar', ['id' => $id])) . '">Editar</a>'
            . ($aluno['ativo'] ? ' <a class="btn btn-destaque" href="' . e(url('agenda', 'novo', ['aluno' => $id])) . '">+ Agendar aula</a>' : '');
        render('alunos/ver', $dados);
    }

    /** RF07/RN07: inativa com motivo, sem apagar o histórico. */
    public function inativar(): void
    {
        $this->exigirPost();
        $aluno = $this->buscar(post_int('id'));
        $motivo = post('motivo');
        if ($motivo === '') {
            avisar('erro', 'Informe o motivo da inativação.');
            redirecionar('alunos', 'ver', ['id' => $aluno['id']]);
        }
        Aluno::inativar((int)$aluno['id'], mb_substr($motivo, 0, 255));
        registrar_log('aluno_inativado', $aluno['nome'] . ': ' . $motivo);
        avisar('ok', 'Aluno inativado. As aulas futuras dele foram canceladas e o histórico foi mantido.');
        redirecionar('alunos', 'ver', ['id' => $aluno['id']]);
    }

    public function reativar(): void
    {
        $this->exigirPost();
        $aluno = $this->buscar(post_int('id'));
        Aluno::reativar((int)$aluno['id']);
        registrar_log('aluno_reativado', $aluno['nome']);
        avisar('ok', 'Aluno reativado.');
        redirecionar('alunos', 'ver', ['id' => $aluno['id']]);
    }

    /* ---------------- apoio ---------------- */

    private function formulario(array $aluno, array $erros): void
    {
        $novo = empty($aluno['id']);
        render('alunos/form', [
            'titulo' => $novo ? 'Novo aluno' : 'Editar aluno',
            'aluno'  => $aluno,
            'erros'  => $erros,
            'novo'   => $novo,
            'cursos' => Curso::ativos(),
            'nichos' => Aluno::nichos(),
        ]);
    }

    /** Lê e valida o formulário. Devolve [dados, erros]. */
    private function lerFormulario(bool $novo): array
    {
        $d = [
            'nome'          => post('nome'),
            'email'         => vazio_null(mb_strtolower(post('email'))),
            'telefone'      => vazio_null(post('telefone')),
            'nicho'         => vazio_null(post('nicho')),
            'data_inicio'   => post('data_inicio'),
            'contrato_link' => vazio_null(post('contrato_link')),
            'nivel_inicial' => vazio_null(post('nivel_inicial')),
            'nivel_alvo'    => vazio_null(post('nivel_alvo')),
            'cena_objetivo' => vazio_null(post('cena_objetivo')),
            'data_cena'     => vazio_null(post('data_cena')),
            'curso_id'      => $novo ? post_int('curso_id') : null,
        ];
        $erros = [];
        if ($d['nome'] === '' || mb_strlen($d['nome']) > 120) {
            $erros[] = 'Informe o nome do aluno (até 120 caracteres).';
        }
        if ($d['email'] !== null && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'O e-mail não é válido.';
        }
        if (!data_valida($d['data_inicio'])) {
            $erros[] = 'Informe a data de início.';
        }
        if ($d['contrato_link'] !== null && !url_valida($d['contrato_link'])) {
            $erros[] = 'O link do contrato precisa começar com http:// ou https://';
        }
        foreach (['nivel_inicial', 'nivel_alvo'] as $n) {
            if ($d[$n] !== null && !isset(NIVEIS[$d[$n]])) {
                $erros[] = 'Nível de fluência inválido.';
            }
        }
        if ($d['data_cena'] !== null && !data_valida($d['data_cena'])) {
            $erros[] = 'A data da cena não é válida.';
        }
        if ($d['curso_id'] !== null && Curso::porId($d['curso_id']) === null) {
            $erros[] = 'Curso não encontrado.';
        }
        foreach (['telefone' => 30, 'nicho' => 80, 'cena_objetivo' => 255, 'email' => 160, 'contrato_link' => 500] as $campo => $max) {
            if ($d[$campo] !== null && mb_strlen($d[$campo]) > $max) {
                $erros[] = "O campo {$campo} passou do limite de {$max} caracteres.";
            }
        }
        return [$d, $erros];
    }

    private function buscar(?int $id): array
    {
        $aluno = $id ? Aluno::porId($id) : null;
        if ($aluno === null) {
            avisar('erro', 'Aluno não encontrado.');
            redirecionar('alunos');
        }
        return $aluno;
    }

    private function exigirPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirecionar('alunos');
        }
    }
}
