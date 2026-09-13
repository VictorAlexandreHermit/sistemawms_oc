<?php
/**
 * app/models/PesquisaOtifModel.php
 * Pesquisas OTIF do cliente final (token, expiração, respostas e envio).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class PesquisaOtifModel
{
    public static function buscarPorToken(string $token): ?array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pesquisas_otif WHERE token_acesso = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function doPedido(int $pedidoId): ?array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pesquisas_otif WHERE pedido_id = :pedido LIMIT 1');
        $stmt->execute([':pedido' => $pedidoId]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function listar(?string $status = null): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT po.*, p.numero_nota_xml, p.cliente_nome, p.cliente_contato
                FROM pesquisas_otif po
                INNER JOIN pedidos p ON p.id = po.pedido_id
                WHERE 1=1';
        if (in_array($status, ['PENDENTE', 'ENVIADO', 'FALHA'], true)) {
            $sql .= ' AND po.status_envio = :status';
        }
        $sql .= ' ORDER BY po.id DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(isset($status) && in_array($status, ['PENDENTE', 'ENVIADO', 'FALHA'], true) ? [':status' => $status] : []);
        return $stmt->fetchAll();
    }

    public static function expirada(array $pesquisa): bool
    {
        $expiracao = new DateTime($pesquisa['data_expiracao']);
        return DateHelper::agora() > $expiracao;
    }

    public static function registraResposta(array $pesquisa, array $dados, ?array $foto1, ?array $foto2): void
    {
        if ($pesquisa['respondido_em'] !== null) {
            throw new RuntimeException('Esta avaliação já foi enviada anteriormente.');
        }

        $base = [
            'SIM' => 'SIM',
            'NAO' => 'NAO',
        ];
        $prazo       = $base[$dados['prazo_cumprido'] ?? ''] ?? null;
        $avaria      = $base[$dados['sem_avaria'] ?? ''] ?? null;
        $conformidade= $base[$dados['conformidade_itens'] ?? ''] ?? null;

        if ($prazo === null || $avaria === null || $conformidade === null) {
            throw new RuntimeException('Responda as 3 perguntas da avaliação.');
        }

        $foto1Url = null;
        $foto2Url = null;
        if ($foto1 !== null && ($foto1['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $foto1Url = UploadHelper::salvarImagem($foto1, 'otif');
        }
        if ($foto2 !== null && ($foto2['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $foto2Url = UploadHelper::salvarImagem($foto2, 'otif');
        }

        $pdo = Database::conexao();
        $sql = 'UPDATE pesquisas_otif
                SET prazo_cumprido = :prazo, sem_avaria = :avaria, conformidade_itens = :conformidade,
                    foto_1_url = :foto1, foto_2_url = :foto2, observacoes = :obs,
                    respondido_em = NOW()
                WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':prazo'       => $prazo,
            ':avaria'      => $avaria,
            ':conformidade'=> $conformidade,
            ':foto1'       => $foto1Url,
            ':foto2'       => $foto2Url,
            ':obs'         => isset($dados['observacoes']) ? mb_substr(trim($dados['observacoes']), 0, 2000) : null,
            ':id'          => $pesquisa['id'],
        ]);
    }

    /**
     * Marca o status de envio do disparo (PENDENTE/ENVIADO/FALHA).
     */
    public static function registrarStatusEnvio(int $pesquisaId, string $status): void
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE pesquisas_otif
                       SET status_envio = :status,
                           data_envio = IF(:status = "ENVIADO", NOW(), data_envio)
                       WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $pesquisaId]);
    }

    public static function taxas(): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT
                    COUNT(*) AS total_respondidas,
                    SUM(prazo_cumprido = "SIM") AS prazo_ok,
                    SUM(sem_avaria = "SIM") AS avaria_ok,
                    SUM(conformidade_itens = "SIM") AS conformidade_ok
                FROM pesquisas_otif
                WHERE respondido_em IS NOT NULL';
        $reg = $pdo->query($sql)->fetch();

        $total = (int) $reg['total_respondidas'];
        $otif = 0;
        if ($total > 0) {
            $perfeitas = 0;
            $todas = $pdo->query('SELECT * FROM pesquisas_otif WHERE respondido_em IS NOT NULL')->fetchAll();
            foreach ($todas as $r) {
                if ($r['prazo_cumprido'] === 'SIM' && $r['sem_avaria'] === 'SIM' && $r['conformidade_itens'] === 'SIM') {
                    $perfeitas++;
                }
            }
            $otif = round(($perfeitas / $total) * 100, 1);
        }

        return [
            'total'   => (int) $total,
            'prazo_ok' => (int) ($reg['prazo_ok'] ?? 0),
            'avaria_ok' => (int) ($reg['avaria_ok'] ?? 0),
            'conformidade_ok' => (int) ($reg['conformidade_ok'] ?? 0),
            'otif_percentual' => $otif,
        ];
    }

    public static function criticas(): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT po.*, p.numero_nota_xml, p.cliente_nome, p.cliente_contato
                FROM pesquisas_otif po
                INNER JOIN pedidos p ON p.id = po.pedido_id
                WHERE po.respondido_em IS NOT NULL
                  AND (po.prazo_cumprido = "NAO" OR po.sem_avaria = "NAO" OR po.conformidade_itens = "NAO")
                ORDER BY po.respondido_em DESC
                LIMIT 20';
        return $pdo->query($sql)->fetchAll();
    }
}