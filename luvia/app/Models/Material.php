<?php
/**
 * Material: e-books, PDFs e outros arquivos por link, gerais ou de um aluno (RF24, RN10).
 */
declare(strict_types=1);

final class Material
{
    public const TIPOS = ['PDF', 'E-book', 'Planilha', 'Áudio', 'Vídeo', 'Apresentação', 'Link'];

    public static function todos(?int $alunoId = null): array
    {
        $sql = 'SELECT m.*, a.nome AS aluno FROM materiais m LEFT JOIN alunos a ON a.id = m.aluno_id';
        $p = [];
        if ($alunoId !== null) {
            $sql .= ' WHERE m.aluno_id = ?';
            $p[] = $alunoId;
        }
        return Db::todos($sql . ' ORDER BY m.aluno_id IS NOT NULL, a.nome, m.titulo', $p);
    }

    /** Materiais do aluno + os gerais (que valem para todos). */
    public static function doAluno(int $alunoId): array
    {
        return Db::todos('SELECT * FROM materiais WHERE aluno_id = ? OR aluno_id IS NULL ORDER BY aluno_id IS NULL, titulo', [$alunoId]);
    }

    public static function criar(?int $alunoId, string $titulo, string $tipo, string $link): void
    {
        Db::executar('INSERT INTO materiais (aluno_id, titulo, tipo, link) VALUES (?, ?, ?, ?)', [$alunoId, $titulo, $tipo, $link]);
    }

    public static function excluir(int $id): void
    {
        Db::executar('DELETE FROM materiais WHERE id = ?', [$id]);
    }
}
