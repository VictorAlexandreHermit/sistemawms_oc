<?php
/**
 * app/models/DivergenciaModel.php
 * Fila de exceções do recebimento (aprovação do Gestor).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class DivergenciaModel
{
    public const MOTIVOS = [
        'FALTA'   => 'Falta de mercadoria (quantidade menor que o XML)',
        'EXCESSO' => 'Excesso de mercadoria (quantidade maior que o XML)',
        'AVARIA'  => 'Mercadoria avariada',
        'PRODUTO_DIFERENTE' => 'Produto divergente do XML',
    ];

    public static function criar(int $pedidoId, int $produtoId, int $qtdXml, int $qtdFisica): void
    {
        $motivo = self::MOTIVOS['FALTA'];
        if ($qtdFisica > $qtdXml) {
            $motivo = self::MOTIVOS['EXCESSO'];
        }

        $pdo = Database::conexao();
        $sql = 'INSERT INTO divergencias_recebimento (pedido_id, produto_id, qtd_xml, qtd_fisica, motivo)
                VALUES (:pedido, :produto, :xml, :fisica, :motivo)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':pedido'  => $pedidoId,
            ':produto' => $produtoId,
            ':xml'     => $qtdXml,
            ':fisica'  => $qtdFisica,
            ':motivo'  => $motivo,
        ]);
    }

    public static function contarPendentes(): int
    {
        $pdo = Database::conexao();
        $reg = $pdo->query('SELECT COUNT(*) AS total FROM divergencias_recebimento WHERE status_aprovacao = "PENDENTE"')->fetch();
        return (int) ($reg['total'] ?? 0);
    }

    public static function pendentes(): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT d.*, p.numero_nota_xml, p.cliente_nome, pr.sku, pr.descricao
                FROM divergencias_recebimento d
                INNER JOIN pedidos p ON p.id = d.pedido_id
                INNER JOIN produtos pr ON pr.id = d.produto_id
                WHERE d.status_aprovacao = "PENDENTE"
                ORDER BY d.created_at DESC';
        return $pdo->query($sql)->fetchAll();
    }

    public static function aprovar(int $id, int $gestorId): void
    {
        $pdo = Database::conexao();
        $div = self::buscarPorId($id);
        if ($div === null) {
            return;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE divergencias_recebimento
                                   SET status_aprovacao = "APROVADO", tratado_por = :gestor
                                   WHERE id = :id');
            $stmt->execute([':gestor' => $gestorId, ':id' => $id]);

            // Consolida a entrada conforme o físico: o item passa a mirar a
            // quantidade conferida (física) nas etapas de guarda, picking e OTIF.
            $up = $pdo->prepare('UPDATE pedido_itens
                                 SET quantidade_esperada = :fisica,
                                     status_conferencia = "CONFERIDO"
                                 WHERE pedido_id = :pedido AND produto_id = :produto');
            $up->execute([
                ':fisica'  => (int) $div['qtd_fisica'],
                ':pedido'  => (int) $div['pedido_id'],
                ':produto' => (int) $div['produto_id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
        }
    }

    public static function rejeitar(int $id, int $gestorId): void
    {
        $pdo = Database::conexao();
        $div = self::buscarPorId($id);
        if ($div === null) {
            return;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE divergencias_recebimento
                                   SET status_aprovacao = "REJEITADO", tratado_por = :gestor
                                   WHERE id = :id');
            $stmt->execute([':gestor' => $gestorId, ':id' => $id]);

            // A entrada já seguiu a conferência física registrada; ajusta a
            // meta do item para evitar bloqueio nas etapas seguintes.
            $up = $pdo->prepare('UPDATE pedido_itens
                                 SET quantidade_esperada = :fisica,
                                     status_conferencia = "CONFERIDO"
                                 WHERE pedido_id = :pedido AND produto_id = :produto');
            $up->execute([
                ':fisica'  => (int) $div['qtd_fisica'],
                ':pedido'  => (int) $div['pedido_id'],
                ':produto' => (int) $div['produto_id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
        }
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM divergencias_recebimento WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function temPendenteParaPedido(int $pedidoId): bool
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT 1 FROM divergencias_recebimento
                               WHERE pedido_id = :p AND status_aprovacao = "PENDENTE" LIMIT 1');
        $stmt->execute([':p' => $pedidoId]);
        return $stmt->fetch() !== false;
    }
}