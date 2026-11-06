<?php
/**
 * Repositório de materiais (RF24) e aulas gravadas (RF25).
 */
declare(strict_types=1);

final class MaterialController
{
    public function index(): void
    {
        $alunoId = get_int('aluno');
        render('materiais/index', [
            'titulo'    => 'Materiais e gravações',
            'materiais' => Material::todos($alunoId),
            'gravacoes' => Gravacao::todas($alunoId),
            'alunos'    => Aluno::ativosParaSelecao(),
            'alunoId'   => $alunoId,
        ]);
    }

    public function salvar(): void
    {
        exigir_post('materiais');
        $alunoId = post_int('aluno_id');
        $titulo = post('titulo');
        $tipo = post('tipo');
        $link = post('link');
        if ($titulo === '' || mb_strlen($titulo) > 160 || !in_array($tipo, Material::TIPOS, true) || !url_valida($link) || mb_strlen($link) > 500) {
            avisar('erro', 'Informe título, tipo e um link que comece com http:// ou https://');
        } elseif ($alunoId !== null && Aluno::porId($alunoId) === null) {
            avisar('erro', 'Aluno não encontrado.');
        } else {
            Material::criar($alunoId, $titulo, $tipo, $link);
            avisar('ok', 'Material cadastrado.');
        }
        voltar(url('materiais'));
    }

    public function excluir(): void
    {
        exigir_post('materiais');
        Material::excluir((int)post_int('id'));
        avisar('ok', 'Material excluído.');
        voltar(url('materiais'));
    }

    public function salvar_gravacao(): void
    {
        exigir_post('materiais');
        $alunoId = post_int('aluno_id');
        $titulo = post('titulo');
        $link = post('link');
        $data = post('data_aula');
        if ($titulo === '' || mb_strlen($titulo) > 160 || !url_valida($link) || mb_strlen($link) > 500 || !data_valida($data)) {
            avisar('erro', 'Informe título, data e um link que comece com http:// ou https://');
        } elseif ($alunoId !== null && Aluno::porId($alunoId) === null) {
            avisar('erro', 'Aluno não encontrado.');
        } else {
            Gravacao::criar($alunoId, $titulo, $link, $data);
            avisar('ok', 'Gravação cadastrada.');
        }
        voltar(url('materiais'));
    }

    public function excluir_gravacao(): void
    {
        exigir_post('materiais');
        Gravacao::excluir((int)post_int('id'));
        avisar('ok', 'Gravação excluída.');
        voltar(url('materiais'));
    }
}
