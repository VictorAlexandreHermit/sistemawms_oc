<?php
/**
 * app/models/AuditoriaModel.php
 * Log inalterável de acertos manuais de estoque.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class AuditoriaModel
{
    /**
     * Realiza o ajuste manual de saldo, atualizando o estoque e gravando o log.
     */
    public static function ajustar(int $produtoId, int $enderecoId, int $novaQuantidade, string $motivoCodigo, ?string $observacoes = null): void
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            $linha = EstoqueModel::linha($produtoId, $enderecoId, 'DISPONIVEL');
            $quantidadeAnterior = $linha !== null ? (int) $linha['quantidade'] : 0;

            if ($novaQuantidade === $quantidadeAnterior) {
                $pdo->rollBack();
                throw new RuntimeException('A nova quantidade é igual à quantidade atual do endereço.');
            }

            if ($linha !== null && $novaQuantidade > 0) {
                $up = $pdo->prepare('UPDATE estoque_saldos SET quantidade = :q, updated_at = NOW() WHERE id = :id');
                $up->execute([':q' => $novaQuantidade, ':id' => $linha['id']]);
            } elseif ($linha !== null && $novaQuantidade === 0) {
                $up = $pdo->prepare('UPDATE estoque_saldos SET quantidade = 0, updated_at = NOW() WHERE id = :id');
                $up->execute([':id' => $linha['id']]);
            } elseif ($novaQuantidade > 0) {
                EstoqueModel::entrada($produtoId, $enderecoId, $novaQuantidade, null, null, 'DISPONIVEL');
            }

            $sql = 'INSERT INTO logs_auditoria_estoque
                        (produto_id, endereco_id, quantidade_anterior, quantidade_nova, motivo_codigo, observacoes, operador_id)
                    VALUES (:produto, :endereco, :anterior, :nova, :motivo, :obs, :operador)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':produto'  => $produtoId,
                ':endereco' => $enderecoId,
                ':anterior' => $quantidadeAnterior,
                ':nova'     => $novaQuantidade,
                ':motivo'   => $motivoCodigo,
                ':obs'      => $observacoes !== null ? mb_substr($observacoes, 0, 500) : null,
                ':operador' => AuthHelper::usuario('id'),
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            LogHelper::registrarErro($e);
            throw $e;
        }
    }

    public static function historico(?string $termo = null): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT l.*, p.sku, p.descricao, p.codigo_barras,
                       e.corredor, e.galpao, e.prateleira, u.nome_completo AS operador_nome
                FROM logs_auditoria_estoque l
                INNER JOIN produtos p ON p.id = l.produto_id
                INNER JOIN enderecos e ON e.id = l.endereco_id
                INNER JOIN usuarios u ON u.id = l.operador_id
                WHERE l.deleted_at IS NULL';
        if ($termo !== null && trim($termo) !== '') {
            $sql .= ' AND (p.sku LIKE :t OR p.descricao LIKE :t OR CONCAT(e.corredor,"-",e.galpao,"-",e.prateleira) LIKE :t)';
        }
        $sql .= ' ORDER BY l.created_at DESC LIMIT 300';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($termo !== null && trim($termo) !== '' ? [':t' => '%' . $termo . '%'] : []);
        return $stmt->fetchAll();
    }

    public static function excluir(int $id): void
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE logs_auditoria_estoque SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
    }
}