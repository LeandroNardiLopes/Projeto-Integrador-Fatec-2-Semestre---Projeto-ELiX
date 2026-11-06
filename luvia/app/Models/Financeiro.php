<?php
/**
 * Financeiro: cobranças, baixa de pagamento, atrasos e pendências (RF26 a RF29).
 */
declare(strict_types=1);

final class Financeiro
{
    public static function listar(?int $alunoId, string $status, string $mes): array
    {
        $sql = 'SELECT f.*, a.nome AS aluno, GREATEST(DATEDIFF(CURRENT_DATE, f.vencimento), 0) AS dias_atraso
                  FROM financeiro f JOIN alunos a ON a.id = f.aluno_id WHERE 1 = 1';
        $p = [];
        if ($alunoId !== null) {
            $sql .= ' AND f.aluno_id = ?';
            $p[] = $alunoId;
        }
        if (in_array($status, ['pendente', 'pago', 'atrasado'], true)) {
            $sql .= ' AND f.status = ?';
            $p[] = $status;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $sql .= ' AND DATE_FORMAT(f.vencimento, "%Y-%m") = ?';
            $p[] = $mes;
        }
        return Db::todos($sql . ' ORDER BY f.vencimento, a.nome', $p);
    }

    public static function pendencias(): array
    {
        return Db::todos('SELECT * FROM vw_financeiro_pendencias');
    }

    public static function totais(): array
    {
        return Db::um('SELECT
              COALESCE(SUM(CASE WHEN status IN ("pendente", "atrasado") THEN valor END), 0) AS a_receber,
              COALESCE(SUM(CASE WHEN status = "atrasado" THEN valor END), 0) AS atrasado,
              COALESCE(SUM(CASE WHEN status = "pago" AND DATE_FORMAT(data_pagamento, "%Y-%m") = DATE_FORMAT(CURRENT_DATE, "%Y-%m") THEN valor END), 0) AS recebido_mes
            FROM financeiro') ?? [];
    }

    public static function doAluno(int $alunoId): array
    {
        return Db::todos('SELECT *, GREATEST(DATEDIFF(CURRENT_DATE, vencimento), 0) AS dias_atraso
                            FROM financeiro WHERE aluno_id = ? ORDER BY vencimento', [$alunoId]);
    }

    public static function criar(int $alunoId, string $descricao, float $valor, string $vencimento): void
    {
        $status = $vencimento < date('Y-m-d') ? 'atrasado' : 'pendente';
        Db::executar('INSERT INTO financeiro (aluno_id, descricao, valor, vencimento, status) VALUES (?, ?, ?, ?, ?)',
            [$alunoId, $descricao, $valor, $vencimento, $status]);
    }

    /** RF26: gera várias parcelas mensais de uma vez (ex.: as 3 parcelas do contrato). */
    public static function gerarSerie(int $alunoId, string $descricao, float $valor, int $quantidade, string $primeiroVencimento): void
    {
        $pdo = Db::conexao();
        $pdo->beginTransaction();
        try {
            $base = new DateTimeImmutable($primeiroVencimento);
            $dia = (int)$base->format('d');
            for ($i = 0; $i < $quantidade; $i++) {
                // Mesmo dia em cada mês; em meses mais curtos, usa o último dia do mês.
                $mes = $base->modify('first day of this month')->modify("+{$i} month");
                $venc = $mes->setDate((int)$mes->format('Y'), (int)$mes->format('m'), min($dia, (int)$mes->format('t')));
                self::criar($alunoId, sprintf('%s %d/%d', $descricao, $i + 1, $quantidade), $valor, $venc->format('Y-m-d'));
            }
            $pdo->commit();
        } catch (Throwable $t) {
            $pdo->rollBack();
            throw $t;
        }
    }

    /** RF27/RN09: baixa de pagamento (procedure). */
    public static function pagar(int $id, string $data, string $forma): void
    {
        Db::procedure('sp_registrar_pagamento', [$id, $data, $forma]);
    }

    public static function excluir(int $id): void
    {
        Db::executar('DELETE FROM financeiro WHERE id = ? AND status <> "pago"', [$id]);
    }

    public static function porId(int $id): ?array
    {
        return Db::um('SELECT * FROM financeiro WHERE id = ?', [$id]);
    }
}
