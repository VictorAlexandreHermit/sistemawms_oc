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

    public static function buscarPorSku(string $sku): ?array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM produtos
                               WHERE sku = :sku AND deleted_at IS NULL
                               LIMIT 1');
        $stmt->execute([':sku' => $sku]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    /**
     * Localiza ou cria um produto a partir de um código de barras digitado
     * na Entrada Manual do Recebimento.
     *
     * O SKU interno (ex.: "SIS-001") é o "código de barras interno à prova de
     * erro": mesmo que o MESMO código de barras do fornecedor seja recebido
     * em dias diferentes, o operador informa um SKU novo (sugerido
     * automaticamente) e o sistema cria outro produto — a movimentação
     * (guarda/endereçamento, separação, expedição) passa a ser SEMPRE por SKU.
     *
     * @param string      $codigo    Código de barras (pode ser repetido entre produtos).
     * @param string      $descricao Descrição informada (opcional).
     * @param string      $curva     Curva ABC (A/B/C, padrão C).
     * @param string|null $sku       SKU interno opcional. Se informado e ainda não
     *                               cadastrado, um novo produto é criado mesmo com
     *                               código de barras repetido.
     * @return array Produto existente ou recém-criado.
     */
    public static function obterOuCriarManual(string $codigo, string $descricao = '', string $curva = 'C', ?string $sku = null): array
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            throw new RuntimeException('Informe um código de barras para a entrada manual.');
        }
        $curva = strtoupper(trim($curva));
        if (!in_array($curva, ['A', 'B', 'C'], true)) {
            $curva = 'C';
        }
        $sku = strtoupper(trim((string) $sku));

        if ($sku !== '') {
            $porSku = self::buscarPorSku($sku);
            if ($porSku !== null) {
                if ($curva !== strtoupper((string) $porSku['curva_abc'])) {
                    $pdo = Database::conexao();
                    $up = $pdo->prepare('UPDATE produtos SET curva_abc = :curva, updated_by = :usuario WHERE id = :id');
                    $up->execute([
                        ':curva'   => $curva,
                        ':usuario' => AuthHelper::usuario('id'),
                        ':id'      => (int) $porSku['id'],
                    ]);
                    $porSku['curva_abc'] = $curva;
                }
                return $porSku;
            }
            $produto = self::criarManual($sku, $codigo, $descricao, $curva);
            return $produto !== null ? $produto : throw new RuntimeException('Não foi possível cadastrar o SKU "' . $sku . '" no catálogo.');
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

        $produto = self::criarManual(self::gerarSkuInterno(), $codigo, $descricao, $curva);
        if ($produto === null) {
            throw new RuntimeException('Não foi possível cadastrar o produto do código "' . $codigo . '" no catálogo.');
        }
        return $produto;
    }

    private static function criarManual(string $sku, string $codigo, string $descricao, string $curva): ?array
    {
        $descricaoExtra = trim($descricao) !== '' ? $descricao : 'Produto recebido por código de barras ' . $codigo;

        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            $sql = 'INSERT INTO produtos (sku, codigo_barras, descricao, unidade_medida, curva_abc, created_by)
                    VALUES (:sku, :codigo, :descricao, :unidade, :curva, :criadoPor)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':sku'        => $sku,
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
            return null;
        }

        return self::buscarPorId($id);
    }

    /**
     * Gera um SKU interno sequencial no formato "SIS-001" (código de barras
     * interno do sistema, curto e fácil de digitar/bipar), sempre único.
     */
    public static function gerarSkuInterno(): string
    {
        $pdo = Database::conexao();
        $stmt = $pdo->query('SELECT MAX(CAST(SUBSTRING(sku, 5) AS UNSIGNED)) AS ultimo
                             FROM produtos WHERE sku LIKE "SIS-%"');
        $linha = $stmt->fetch();
        $numero = (int) ($linha['ultimo'] ?? 0) + 1;

        $sku = 'SIS-' . str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
        while (self::skuExiste($sku)) {
            $numero++;
            $sku = 'SIS-' . str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
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
            // Pesquisa apenas por SKU ou descrição: o código de barras pode se repetir.
            $sql .= ' AND (sku LIKE :t OR descricao LIKE :t)';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC';
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