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

    /**
     * Localiza ou cria um produto a partir de um código de barras digitado
     * na Entrada Manual do Recebimento. Se o código não existir no catálogo,
     * o produto é cadastrado automaticamente com um SKU interno único
     * ("código de barras interno" gerado pelo sistema), evitando duplicidade
     * entre fornecedores. O catálogo é atualizado no ato do recebimento.
     * A Curva ABC escolhida (A/B/C) define a prioridade de movimentação.
     *
     * @return array Produto existente ou recém-criado.
     */
    public static function obterOuCriarManual(string $codigo, string $descricao = '', string $curva = 'C'): array
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            throw new RuntimeException('Informe um código de barras para a entrada manual.');
        }
        $curva = strtoupper(trim($curva));
        if (!in_array($curva, ['A', 'B', 'C'], true)) {
            $curva = 'C';
        }

        $existente = self::buscarPorCodigoBarras($codigo);
        if ($existente !== null) {
            if ($curva !== strtoupper((string) $existente['curva_abc'])) {
                $pdo = Database::conexao();
                $up = $pdo->prepare('UPDATE produtos SET curva_abc = :curva, updated_by = :usuario WHERE id = :id');
                $up->execute([
                    ':curva'   => $curva,
                    ':usuario' => AuthHelper::usuario('id'),
                    ':id'      => (int) $existente['id'],
                ]);
                $existente['curva_abc'] = $curva;
            }
            return $existente;
        }

        $descricaoExtra = trim($descricao) !== '' ? $descricao : 'Produto recebido por código de barras ' . $codigo;

        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            $sql = 'INSERT INTO produtos (sku, codigo_barras, descricao, unidade_medida, curva_abc, created_by)
                    VALUES (:sku, :codigo, :descricao, :unidade, :curva, :criadoPor)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':sku'        => self::gerarSkuInterno(),
                ':codigo'     => mb_substr($codigo, 0, 100),
                ':descricao' => mb_substr($descricaoExtra, 0, 255),
                ':unidade'    => 'UN',
                ':curva'      => $curva,
                ':criadoPor'  => AuthHelper::usuario('id'),
            ]);
            $id = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
            throw new RuntimeException('Não foi possível cadastrar o produto do código "' . $codigo . '" no catálogo.');
        }

        return self::buscarPorId($id);
    }

    /**
     * Gera um SKU interno sequencial no formato "WM-000001" (código de barras
     * interno do sistema), sempre único.
     */
    private static function gerarSkuInterno(): string
    {
        $pdo = Database::conexao();
        $stmt = $pdo->query('SELECT MAX(CAST(SUBSTRING(sku, 7) AS UNSIGNED)) AS ultimo
                             FROM produtos WHERE sku LIKE "WM-%"');
        $linha = $stmt->fetch();
        $numero = (int) ($linha['ultimo'] ?? 0) + 1;

        $sku = 'WM-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
        while (self::skuExiste($sku)) {
            $numero++;
            $sku = 'WM-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
        }
        return $sku;
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

    /**
     * Exclusão definitiva (soft delete) do produto NO SISTEMA INTEIRO.
     * Remove também as referências em qualquer processo logístico (itens de
     * pedidos, divergências, avarias, saldos e auditoria) para o produto
     * desaparecer do Kanban, Putaway, Separar/Expedir etc. Exclusivo do
     * Administrador.
     */
    public static function excluir(int $id): void
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            // Zera/remove o produto em todos os fluxos e no estoque
            $pdo->prepare('DELETE FROM pedido_itens WHERE produto_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM divergencias_recebimento WHERE produto_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM avarias WHERE produto_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM estoque_saldos WHERE produto_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM logs_auditoria_estoque WHERE produto_id = :id')->execute([':id' => $id]);
            $stmt = $pdo->prepare('UPDATE produtos SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $stmt->execute([':id' => $id]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
            throw $e;
        }
    }
}