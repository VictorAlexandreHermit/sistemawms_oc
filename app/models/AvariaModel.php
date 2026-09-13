<?php
/**
 * app/models/AvariaModel.php
 * Registro de avarias e transferência automática para a Quarentena.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class AvariaModel
{
    /**
     * Registra a avaria e transfere o saldo Disponível para o endereço virtual de Quarentena.
     *
     * @throws RuntimeException se não houver saldo suficiente ou endereço de quarentena.
     */
    public static function registrar(array $dados): void
    {
        $produtoId   = (int) $dados['produto_id'];
        $enderecoId  = (int) $dados['endereco_id'];
        $quantidade  = (int) $dados['quantidade'];
        $motivo      = trim($dados['motivo'] ?? 'Avaria identificada');
        $operadorId  = AuthHelper::usuario('id');
        $pedidoId    = isset($dados['pedido_id']) && $dados['pedido_id'] !== '' ? (int) $dados['pedido_id'] : null;

        if ($quantidade <= 0) {
            throw new RuntimeException('A quantidade avariada deve ser maior que zero.');
        }

        $quarentena = EnderecoModel::quarentena();
        if ($quarentena === null) {
            throw new RuntimeException('Endereço virtual de Quarentena não configurado.');
        }

        $saldoAtual = EstoqueModel::saldoTotalDisponivel($produtoId);
        if ($saldoAtual < $quantidade) {
            throw new RuntimeException('Saldo disponível ('.$saldoAtual.') insuficiente para a quantidade avariada informada ('.$quantidade.').');
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            EstoqueModel::transferir($produtoId, $enderecoId, (int) $quarentena['id'], $quantidade);

            $foto = null;
            if (isset($dados['foto_url']) && !empty($dados['foto_url'])) {
                $foto = $dados['foto_url'];
            }

            $sql = 'INSERT INTO avarias (produto_id, endereco_id, pedido_id, quantidade, foto_url, motivo, operador_id, etapa)
                    VALUES (:produto, :endereco, :pedido, :qtd, :foto, :motivo, :operador, :etapa)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':produto'  => $produtoId,
                ':endereco' => $enderecoId,
                ':pedido'   => $pedidoId,
                ':qtd'      => $quantidade,
                ':foto'     => $foto,
                ':motivo'   => mb_substr($motivo, 0, 255),
                ':operador' => $operadorId,
                ':etapa'    => $dados['etapa'] ?? 'OUTROS',
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
            throw $e;
        }
    }

    public static function registrarComFoto(array $dados, ?array $arquivoFoto): void
    {
        if ($arquivoFoto !== null && ($arquivoFoto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $caminho = UploadHelper::salvarImagem($arquivoFoto, 'avarias');
            $dados['foto_url'] = $caminho;
        }
        self::registrar($dados);
    }

    public static function listar(?int $produtoId = null): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT a.*, p.sku, p.descricao, p.codigo_barras,
                       e.rua, e.predio, e.nivel, u.nome_completo AS operador_nome
                FROM avarias a
                INNER JOIN produtos p ON p.id = a.produto_id
                INNER JOIN enderecos e ON e.id = a.endereco_id
                INNER JOIN usuarios u ON u.id = a.operador_id
                WHERE 1=1';
        if ($produtoId !== null) {
            $sql .= ' AND a.produto_id = :produto';
        }
        $sql .= ' ORDER BY a.created_at DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($produtoId !== null ? [':produto' => $produtoId] : []);
        return $stmt->fetchAll();
    }

    public static function ocorrenciasPorFornecedor(): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT COALESCE(p.fornecedor_nome, "NÃO INFORMADO") AS fornecedor,
                       COUNT(a.id) AS ocorrencias,
                       SUM(a.quantidade) AS total_avariado
                FROM avarias a
                LEFT JOIN pedidos p ON p.id = a.pedido_id
                GROUP BY fornecedor
                ORDER BY ocorrencias DESC
                LIMIT 15';
        return $pdo->query($sql)->fetchAll();
    }

    public static function saldoEmQuarentena(): int
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantidade),0) AS total FROM estoque_saldos WHERE status_saldo = "QUARENTENA"');
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }
}