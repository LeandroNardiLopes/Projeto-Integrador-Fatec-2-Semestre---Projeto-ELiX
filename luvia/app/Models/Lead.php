<?php
/**
 * Lead: possíveis alunos e funil de acompanhamento (RF10 a RF12).
 */
declare(strict_types=1);

final class Lead
{
    public const STATUS = ['novo' => 'Novo', 'em_contato' => 'Em contato', 'convertido' => 'Convertido', 'desistiu' => 'Desistiu'];
    public const ORIGENS = ['Instagram', 'LinkedIn', 'Indicação', 'Site', 'WhatsApp', 'Evento', 'Outro'];

    public static function funil(): array
    {
        return Db::todos('SELECT * FROM vw_funil_leads');
    }

    public static function listar(string $status = '', string $busca = ''): array
    {
        $sql = 'SELECT l.*, a.nome AS aluno_nome,
                       (SELECT MIN(CONCAT(ag.data_aula, " ", ag.hora_inicio)) FROM agendamentos ag
                         WHERE ag.lead_id = l.id AND ag.status = "agendada" AND ag.data_aula >= CURRENT_DATE) AS proxima_entrevista
                  FROM leads l LEFT JOIN alunos a ON a.id = l.aluno_id WHERE 1 = 1';
        $p = [];
        if (isset(self::STATUS[$status])) {
            $sql .= ' AND l.status = ?';
            $p[] = $status;
        }
        if ($busca !== '') {
            $sql .= ' AND (l.nome LIKE ? OR l.email LIKE ? OR l.nicho LIKE ?)';
            $like = '%' . $busca . '%';
            array_push($p, $like, $like, $like);
        }
        return Db::todos($sql . ' ORDER BY FIELD(l.status, "novo", "em_contato", "convertido", "desistiu"), l.criado_em DESC', $p);
    }

    public static function porId(int $id): ?array
    {
        return Db::um('SELECT * FROM leads WHERE id = ?', [$id]);
    }

    public static function criar(array $d): int
    {
        Db::executar('INSERT INTO leads (nome, email, telefone, origem, nicho, status, observacao) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$d['nome'], $d['email'], $d['telefone'], $d['origem'], $d['nicho'], $d['status'], $d['observacao']]);
        return Db::ultimoId();
    }

    public static function atualizar(int $id, array $d): void
    {
        Db::executar('UPDATE leads SET nome = ?, email = ?, telefone = ?, origem = ?, nicho = ?, status = ?, observacao = ? WHERE id = ?',
            [$d['nome'], $d['email'], $d['telefone'], $d['origem'], $d['nicho'], $d['status'], $d['observacao'], $id]);
    }

    public static function mudarStatus(int $id, string $status): void
    {
        Db::executar('UPDATE leads SET status = ? WHERE id = ? AND status <> "convertido"', [$status, $id]);
    }

    /** RF12/RN09: cria o aluno a partir do lead (procedure). Devolve o id do aluno. */
    public static function converter(int $id): int
    {
        $r = Db::procedure('sp_converter_lead', [$id], ['@novo_aluno']);
        return (int)$r['@novo_aluno'];
    }
}
