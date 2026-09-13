<?php
/**
 * app/models/EnderecoModel.php
 * Estrutura física do galpão (Corredor - Galpão - Prateleira).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class EnderecoModel
{
    public static function buscarPorId(int $id, $incluirExcluidos = false): ?array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM enderecos WHERE id = :id';
        if (!$incluirExcluidos) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function buscarPorCodigo(string $codigo): ?array
    {
        $code = strtoupper(trim($codigo));
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM enderecos
                WHERE deleted_at IS NULL
                  AND (UPPER(CONCAT(corredor, "-", galpao, "-", prateleira)) = :codigo OR UPPER(CONCAT(corredor, galpao, prateleira)) = :codigo2)
                LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':codigo' => $code,
            ':codigo2' => str_replace('-', '', $code),
        ]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function quarentena(): ?array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM enderecos WHERE quarantena = 1 AND deleted_at IS NULL LIMIT 1');
        $stmt->execute();
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function listar(?string $termo = null, $incluirExcluidos = false): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT e.*,
                       (SELECT COUNT(*) FROM estoque_saldos es WHERE es.endereco_id = e.id AND es.quantidade > 0) AS posicoes_ocupadas
                FROM enderecos e WHERE 1=1';
        if (!$incluirExcluidos) {
            $sql .= ' AND e.deleted_at IS NULL';
        }
        if ($termo !== null && trim($termo) !== '') {
            $sql .= ' AND (e.corredor LIKE :t OR e.galpao LIKE :t OR e.prateleira LIKE :t OR CONCAT(e.corredor,\'-\',e.galpao,\'-\',e.prateleira) LIKE :t)';
        }
        $sql .= ' ORDER BY e.quarantena ASC, e.corredor ASC, e.galpao ASC, e.prateleira ASC';
        $stmt = $pdo->prepare($sql);
        if ($termo !== null && trim($termo) !== '') {
            $stmt->execute([':t' => '%' . $termo . '%']);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public static function salvar(array $dados, ?int $id = null): int
    {
        $pdo = Database::conexao();
        $dados['corredor']    = strtoupper(trim($dados['corredor']));
        $dados['galpao']      = strtoupper(trim($dados['galpao']));
        $dados['prateleira']  = strtoupper(trim($dados['prateleira']));

        if ($id === null) {
            $sql = 'INSERT INTO enderecos (corredor, galpao, prateleira, descricao, capacidade_maxima)
                    VALUES (:corredor, :galpao, :prateleira, :descricao, :capacidade)';
        } else {
            $sql = 'UPDATE enderecos
                    SET corredor = :corredor, galpao = :galpao, prateleira = :prateleira,
                        descricao = :descricao, capacidade_maxima = :capacidade
                    WHERE id = :id AND deleted_at IS NULL';
        }
        $stmt = $pdo->prepare($sql);
        $params = [
            ':corredor'    => $dados['corredor'],
            ':galpao'      => $dados['galpao'],
            ':prateleira'  => $dados['prateleira'],
            ':descricao'   => $dados['descricao'] ?? '',
            ':capacidade'  => (int) ($dados['capacidade_maxima'] ?? 1000),
        ];
        if ($id !== null) {
            $params[':id'] = $id;
        }
        $stmt->execute($params);
        return $id !== null ? $id : (int) $pdo->lastInsertId();
    }

    public static function excluir(int $id): void
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE enderecos SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
    }

    public static function formato(array $endereco): string
    {
        return $endereco['corredor'] . '-' . $endereco['galpao'] . '-' . $endereco['prateleira'];
    }
}