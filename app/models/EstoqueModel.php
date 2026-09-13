<?php
/**
 * app/models/EstoqueModel.php
 * Saldos de estoque com atualização em tempo real via transações.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class EstoqueModel
{
    /**
     * Adiciona quantidade ao saldo de um produto em um endereço.
     */
    public static function entrada(int $produtoId, int $enderecoId, int $quantidade, ?string $lote = null, ?string $validade = null, string $status = 'DISPONIVEL'): void
    {
        if ($quantidade <= 0) {
            return;
        }
        $pdo = Database::conexao();
        $linha = self::linha($produtoId, $enderecoId, $status, $lote, $validade);

        if ($linha !== null) {
            $sql = 'UPDATE estoque_saldos SET quantidade = quantidade + :qtd, updated_at = NOW()
                    WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':qtd' => $quantidade, ':id' => $linha['id']]);
        } else {
            $sql = 'INSERT INTO estoque_saldos (produto_id, endereco_id, quantidade, lote, data_validade, status_saldo)
                    VALUES (:produto, :endereco, :qtd, :lote, :validade, :status)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':produto'  => $produtoId,
                ':endereco' => $enderecoId,
                ':qtd'      => $quantidade,
                ':lote'     => $lote,
                ':validade' => $validade,
                ':status'   => $status,
            ]);
        }
    }

    /**
     * Retira quantidade de um saldo específico. Lança exceção se saldo insuficiente.
     */
    public static function saida(int $produtoId, int $enderecoId, int $quantidade, string $status = 'DISPONIVEL'): void
    {
        if ($quantidade <= 0) {
            return;
        }
        $pdo = Database::conexao();
        $linha = self::linha($produtoId, $enderecoId, $status);
        if ($linha === null || (int) $linha['quantidade'] < $quantidade) {
            throw new RuntimeException('Saldo insuficiente para a movimentação.');
        }

        $nova = (int) $linha['quantidade'] - $quantidade;
        $sql = 'UPDATE estoque_saldos SET quantidade = :qtd, updated_at = NOW() WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':qtd' => $nova, ':id' => $linha['id']]);
    }

    /**
     * Transfere saldo entre endereços (ex.: Disponível -> Quarentena).
     */
    public static function transferir(int $produtoId, int $enderecoOrigemId, int $enderecoDestinoId, int $quantidade): void
    {
        $pdo = Database::conexao();
        $deveCommitar = !$pdo->inTransaction();
        if ($deveCommitar) {
            $pdo->beginTransaction();
        }
        try {
            self::saida($produtoId, $enderecoOrigemId, $quantidade, 'DISPONIVEL');
            self::entrada($produtoId, $enderecoDestinoId, $quantidade, null, null, 'QUARENTENA');
            if ($deveCommitar) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($deveCommitar) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function linha(int $produtoId, int $enderecoId, string $status, ?string $lote = null, ?string $validade = null): ?array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM estoque_saldos
                WHERE produto_id = :p AND endereco_id = :e AND status_saldo = :s';
        $params = [':p' => $produtoId, ':e' => $enderecoId, ':s' => $status];
        if ($lote !== null) {
            $sql .= ' AND (lote IS NULL OR lote = :lote)';
            $params[':lote'] = $lote;
        }
        if ($validade !== null) {
            $sql .= ' AND (data_validade IS NULL OR data_validade = :val)';
            $params[':val'] = $validade;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function saldoTotalDisponivel(int $produtoId): int
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantidade),0) AS total FROM estoque_saldos
                               WHERE produto_id = :p AND status_saldo = :s');
        $stmt->execute([':p' => $produtoId, ':s' => 'DISPONIVEL']);
        return (int) $stmt->fetch()['total'];
    }

    public static function listarPorEndereco(int $enderecoId): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT es.*, p.sku, p.codigo_barras, p.descricao, p.unidade_medida, p.curva_abc,
                       e.rua, e.predio, e.nivel
                FROM estoque_saldos es
                INNER JOIN produtos p ON p.id = es.produto_id AND p.deleted_at IS NULL
                INNER JOIN enderecos e ON e.id = es.endereco_id
                WHERE es.endereco_id = :endereco AND es.quantidade > 0
                ORDER BY p.sku';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':endereco' => $enderecoId]);
        return $stmt->fetchAll();
    }

    /**
     * Consulta rápida de localização: um endereço pode conter vários produtos/saldos.
     */
    public static function consultarPorProduto(string $codigoOuSku): array
    {
        $produto = ProdutoModel::buscarPorCodigoBarras($codigoOuSku);
        if ($produto === null) {
            return [];
        }
        $pdo = Database::conexao();
        $sql = 'SELECT es.*, e.rua, e.predio, e.nivel,
                       CONCAT(e.rua, "-", e.predio, "-", e.nivel) AS endereco_codigo,
                       es.quantidade AS saldo
                FROM estoque_saldos es
                INNER JOIN enderecos e ON e.id = es.endereco_id AND e.deleted_at IS NULL
                WHERE es.produto_id = :p AND es.quantidade > 0
                  AND es.status_saldo = "DISPONIVEL" AND e.quarantena = 0
                ORDER BY e.rua, e.predio, e.nivel';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':p' => $produto['id']]);
        return $stmt->fetchAll();
    }
}