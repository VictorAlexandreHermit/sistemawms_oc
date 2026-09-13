<?php
/**
 * app/models/EnderecoModel.php
 * Estrutura física do galpão (Rua - Prédio - Nível).
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
                  AND (UPPER(CONCAT(rua, "-", predio, "-", nivel)) = :codigo OR UPPER(CONCAT(rua, predio, nivel)) = :codigo2)
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
            $sql .= ' AND (e.rua LIKE :t OR e.predio LIKE :t OR e.nivel LIKE :t OR CONCAT(e.rua,\'-\',e.predio,\'-\',e.nivel) LIKE :t)';
        }
        $sql .= ' ORDER BY e.quarantena ASC, e.rua ASC, e.predio ASC, e.nivel ASC';
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
        $dados['rua']    = strtoupper(trim($dados['rua']));
        $dados['predio'] = strtoupper(trim($dados['predio']));
        $dados['nivel']  = strtoupper(trim($dados['nivel']));

        if ($id === null) {
            $sql = 'INSERT INTO enderecos (rua, predio, nivel, descricao, capacidade_maxima)
                    VALUES (:rua, :predio, :nivel, :descricao, :capacidade)';
        } else {
            $sql = 'UPDATE enderecos
                    SET rua = :rua, predio = :predio, nivel = :nivel,
                        descricao = :descricao, capacidade_maxima = :capacidade
                    WHERE id = :id AND deleted_at IS NULL';
        }
        $stmt = $pdo->prepare($sql);
        $params = [
            ':rua'        => $dados['rua'],
            ':predio'     => $dados['predio'],
            ':nivel'      => $dados['nivel'],
            ':descricao'  => $dados['descricao'] ?? '',
            ':capacidade' => (int) ($dados['capacidade_maxima'] ?? 1000),
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
        return $endereco['rua'] . '-' . $endereco['predio'] . '-' . $endereco['nivel'];
    }
}