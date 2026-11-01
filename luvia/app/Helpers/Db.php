<?php
/**
 * Db: conexão com o MySQL (PDO) e funções para chamar views e procedures.
 * Toda consulta usa prepared statements (proteção contra SQL injection).
 */
declare(strict_types=1);

final class Db
{
    private static ?PDO $pdo = null;

    public static function conexao(): PDO
    {
        if (self::$pdo === null) {
            $c = $GLOBALS['config']['db'];
            $dsn = "mysql:host={$c['host']};port={$c['porta']};dbname={$c['nome']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $c['usuario'], $c['senha'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => false, // um comando por vez (mais seguro)
            ]);
            // Datas do banco no horário de Brasília (CURRENT_DATE, NOW()).
            $offset = (new DateTime('now', new DateTimeZone($GLOBALS['config']['fuso'])))->format('P');
            self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '{$offset}'");
        }
        return self::$pdo;
    }

    /** Executa uma consulta e devolve todas as linhas. */
    public static function todos(string $sql, array $params = []): array
    {
        $st = self::conexao()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** Executa uma consulta e devolve a primeira linha (ou null). */
    public static function um(string $sql, array $params = []): ?array
    {
        $st = self::conexao()->prepare($sql);
        $st->execute($params);
        $linha = $st->fetch();
        return $linha === false ? null : $linha;
    }

    /** Executa um comando (INSERT, UPDATE, DELETE) e devolve as linhas afetadas. */
    public static function executar(string $sql, array $params = []): int
    {
        $st = self::conexao()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function ultimoId(): int
    {
        return (int) self::conexao()->lastInsertId();
    }

    /**
     * Chama uma stored procedure.
     * $saidas: nomes das variáveis OUT (ex.: ['@id']); devolve os valores delas.
     */
    public static function procedure(string $nome, array $params = [], array $saidas = []): array
    {
        if (!preg_match('/^sp_[a-z_]+$/', $nome)) {
            throw new InvalidArgumentException('Procedure inválida.');
        }
        $marcas = array_merge(array_fill(0, count($params), '?'), $saidas);
        $st = self::conexao()->prepare("CALL {$nome}(" . implode(', ', $marcas) . ')');
        $st->execute(array_values($params));
        while ($st->nextRowset()) { /* consome resultados extras */ }
        $st->closeCursor();
        if ($saidas === []) {
            return [];
        }
        return self::um('SELECT ' . implode(', ', $saidas));
    }

    /**
     * Converte um erro do banco numa mensagem para a mentora.
     * As procedures avisam regras de negócio com SIGNAL (código 1644).
     */
    public static function mensagemDeErro(PDOException $e): string
    {
        $codigo = $e->errorInfo[1] ?? null;
        if ($codigo === 1644) {
            return (string) $e->errorInfo[2];
        }
        if ($codigo === 1062) {
            return 'Já existe um registro com esses dados.';
        }
        if ($codigo === 1451) {
            return 'Não é possível excluir: existem registros ligados a este item.';
        }
        if ($codigo === 3819) {
            return 'Os dados informados não passaram na validação do banco. Confira os valores.';
        }
        error_log($e->getMessage());
        return 'Não foi possível concluir a operação. Tente novamente.';
    }
}
