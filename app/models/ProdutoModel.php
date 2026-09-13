<?php
/**
 * app/models/ProdutoModel.php
 * Catálogo de produtos (SKU, código de barras, Curva ABC).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class ProdutoModel
{
    public static function buscarPorId(int $id, $incluirExcluidos = false): ?array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM produtos WHERE id = :id';
        if (!$incluirExcluidos) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function buscarPorCodigoBarras(string $codigo): ?array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM produtos
                WHERE (codigo_barras = :codigo OR sku = :codigo)
                  AND deleted_at IS NULL
                LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    /**
     * Localiza ou cria um produto a partir dos dados de um XML de NF-e.
     */
    public static function obterOuCriar(array $dadosXml): array
    {
        $pdo = Database::conexao();
        $codigoBarras = trim($dadosXml['codigo_barras']);
        if ($codigoBarras === '') {
            $codigoBarras = trim($dadosXml['sku']);
        }

        $existente = self::buscarPorCodigoBarras($codigoBarras);
        if ($existente !== null) {
            return $existente;
        }

        $sku = trim($dadosXml['sku']);
        // Garante unicidade do SKU caso ja exista
        $skuBase = $sku !== '' ? $sku : $codigoBarras;
        $sufixo = 1;
        while (self::skuExiste($skuBase)) {
            $sufixo++;
            $skuBase = $sku . '-' . $sufixo;
        }

        $sql = 'INSERT INTO produtos (sku, codigo_barras, descricao, unidade_medida, curva_abc, created_by)
                VALUES (:sku, :codigo, :descricao, :unidade, :curva, :criadoPor)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sku'        => $skuBase,
            ':codigo'     => $codigoBarras,
            ':descricao' => mb_substr(trim($dadosXml['descricao']) ?: 'Produto sem descrição', 0, 255),
            ':unidade'    => mb_substr(trim($dadosXml['unidade']) ?: 'UN', 0, 10),
            ':curva'      => 'C',
            ':criadoPor'  => AuthHelper::usuario('id'),
        ]);

        return self::buscarPorId((int) $pdo->lastInsertId());
    }

    private static function skuExiste(string $sku): bool
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT 1 FROM produtos WHERE sku = :sku LIMIT 1');
        $stmt->execute([':sku' => $sku]);
        return $stmt->fetch() !== false;
    }

    public static function listar(?string $termo = null, $incluirExcluidos = false): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM produtos WHERE 1=1';
        if (!$incluirExcluidos) {
            $sql .= ' AND deleted_at IS NULL';
        }
        if ($termo !== null && trim($termo) !== '') {
            $sql .= ' AND (sku LIKE :t OR codigo_barras LIKE :t OR descricao LIKE :t)';
        }
        $sql .= ' ORDER BY curva_abc ASC, sku ASC';
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
        $usuarioId = AuthHelper::usuario('id');

        if ($id === null) {
            $sql = 'INSERT INTO produtos (sku, codigo_barras, descricao, unidade_medida, curva_abc, created_by, updated_by)
                    VALUES (:sku, :codigo, :descricao, :unidade, :curva, :cb, :ub)';
        } else {
            $sql = 'UPDATE produtos
                    SET sku = :sku, codigo_barras = :codigo, descricao = :descricao,
                        unidade_medida = :unidade, curva_abc = :curva, updated_by = :ub
                    WHERE id = :id AND deleted_at IS NULL';
        }
        $stmt = $pdo->prepare($sql);
        $params = [
            ':sku'        => $dados['sku'],
            ':codigo'     => $dados['codigo_barras'],
            ':descricao' => $dados['descricao'],
            ':unidade'    => $dados['unidade_medida'],
            ':curva'      => strtoupper($dados['curva_abc']),
            ':ub'         => $usuarioId,
        ];
        if ($id !== null) {
            $params[':id'] = $id;
        } else {
            $params[':cb'] = $usuarioId;
        }
        $stmt->execute($params);
        return $id !== null ? $id : (int) $pdo->lastInsertId();
    }

    public static function excluir(int $id): void
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE produtos SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
    }
}