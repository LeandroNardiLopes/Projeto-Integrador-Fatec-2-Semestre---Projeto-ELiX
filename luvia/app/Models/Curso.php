<?php
/**
 * Curso: modelos de curso, módulos e lista semanal (RF18).
 */
declare(strict_types=1);

final class Curso
{
    public static function resumo(): array
    {
        return Db::todos('SELECT * FROM vw_cursos_resumo');
    }

    public static function todos(): array
    {
        return Db::todos('SELECT c.*, (SELECT COUNT(*) FROM alunos a WHERE a.curso_id = c.id AND a.ativo = 1) AS alunos_ativos
                            FROM cursos c ORDER BY c.ativo DESC, c.id');
    }

    public static function ativos(): array
    {
        return Db::todos('SELECT id, nome, modalidade, aulas_online_semana, aulas_gravadas_semana, duracao_semanas
                            FROM cursos WHERE ativo = 1 ORDER BY id');
    }

    public static function porId(int $id): ?array
    {
        return Db::um('SELECT * FROM cursos WHERE id = ?', [$id]);
    }

    public static function criar(array $d): int
    {
        Db::executar('INSERT INTO cursos (nome, modalidade, aulas_online_semana, aulas_gravadas_semana, duracao_semanas, descricao, ativo)
                      VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$d['nome'], $d['modalidade'], $d['online'], $d['gravadas'], $d['semanas'], $d['descricao'], $d['ativo']]);
        return Db::ultimoId();
    }

    public static function atualizar(int $id, array $d): void
    {
        Db::executar('UPDATE cursos SET nome = ?, modalidade = ?, aulas_online_semana = ?, aulas_gravadas_semana = ?,
                             duracao_semanas = ?, descricao = ?, ativo = ? WHERE id = ?',
            [$d['nome'], $d['modalidade'], $d['online'], $d['gravadas'], $d['semanas'], $d['descricao'], $d['ativo'], $id]);
    }

    public static function modulos(int $cursoId): array
    {
        return Db::todos('SELECT * FROM curso_modulos WHERE curso_id = ? ORDER BY ordem', [$cursoId]);
    }

    public static function aulasModelo(int $cursoId): array
    {
        return Db::todos('SELECT am.*, m.titulo AS modulo FROM curso_aulas_modelo am
                            JOIN curso_modulos m ON m.id = am.modulo_id
                           WHERE am.curso_id = ? ORDER BY am.semana', [$cursoId]);
    }

    public static function salvarModulo(int $cursoId, ?int $id, array $d): void
    {
        if ($id) {
            Db::executar('UPDATE curso_modulos SET ordem = ?, titulo = ?, pilar = ?, semana_inicio = ?, semana_fim = ?, objetivo = ?
                           WHERE id = ? AND curso_id = ?',
                [$d['ordem'], $d['titulo'], $d['pilar'], $d['ini'], $d['fim'], $d['objetivo'], $id, $cursoId]);
        } else {
            Db::executar('INSERT INTO curso_modulos (curso_id, ordem, titulo, pilar, semana_inicio, semana_fim, objetivo)
                          VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$cursoId, $d['ordem'], $d['titulo'], $d['pilar'], $d['ini'], $d['fim'], $d['objetivo']]);
        }
    }

    public static function excluirModulo(int $id): void
    {
        Db::executar('DELETE FROM curso_modulos WHERE id = ?', [$id]);
    }

    public static function salvarAula(int $cursoId, ?int $id, array $d): void
    {
        if ($id) {
            Db::executar('UPDATE curso_aulas_modelo SET modulo_id = ?, semana = ?, titulo = ?, estrutura = ?, tarefa = ?
                           WHERE id = ? AND curso_id = ?',
                [$d['modulo_id'], $d['semana'], $d['titulo'], $d['estrutura'], $d['tarefa'], $id, $cursoId]);
        } else {
            Db::executar('INSERT INTO curso_aulas_modelo (curso_id, modulo_id, semana, titulo, estrutura, tarefa)
                          VALUES (?, ?, ?, ?, ?, ?)',
                [$cursoId, $d['modulo_id'], $d['semana'], $d['titulo'], $d['estrutura'], $d['tarefa']]);
        }
    }

    public static function excluirAula(int $id): void
    {
        Db::executar('DELETE FROM curso_aulas_modelo WHERE id = ?', [$id]);
    }
}
