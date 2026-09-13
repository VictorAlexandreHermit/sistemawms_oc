<?php
/**
 * app/controllers/GuardaController.php
 * Endereçamento e guarda (Putaway): instruções e confirmação por bipagem.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class GuardaController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $cargas = PedidoModel::listarPorStatus('A_ARMAZENAR');

        $detalhes = [];
        foreach ($cargas as $carga) {
            $detalhes[$carga['id']] = [
                'carga'    => $carga,
                'pendentes'=> PedidoModel::itensPendentesDeGuarda((int) $carga['id']),
            ];
        }

        ViewHelper::render('guarda/index', [
            'titulo'    => 'Guarda (Putaway)',
            'subtitulo' => 'Transporte os volumes conferidos até o endereço físico indicado.',
            'detalhes'  => $detalhes,
        ]);
    }

    public function actionExecutar(int $pedidoId): void
    {
        AuthHelper::requireLogin();

        $pedido = PedidoModel::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] !== 'A_ARMAZENAR') {
            ViewHelper::setFlash('erro', 'Carga não disponível para guarda.');
            Router::redirecionar('guarda');
        }

        $pendentes = PedidoModel::itensPendentesDeGuarda($pedidoId);
        foreach ($pendentes as &$item) {
            $item['endereco_sugerido'] = self::sugerirEndereco((int) $item['produto_id']);
        }
        unset($item);

        ViewHelper::render('guarda/executar', [
            'titulo'    => 'Instruções de Guarda',
            'subtitulo' => 'Nota ' . SecurityHelper::e($pedido['numero_nota_xml']) . ' — bipe o produto e o endereço físico.',
            'pedido'    => $pedido,
            'pendentes' => $pendentes,
        ]);
    }

    public function actionConfirmar(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $produtoCodigo = trim($_POST['produto'] ?? '');
        $enderecoCodigo = trim($_POST['endereco'] ?? '');

        if ($produtoCodigo === '' || $enderecoCodigo === '') {
            ViewHelper::setFlash('erro', 'Bipe o produto e o endereço físico de destino.');
            Router::redirecionar('guarda/executar/' . $pedidoId);
        }

        $produto = ProdutoModel::buscarPorCodigoBarras($produtoCodigo);
        if ($produto === null) {
            ViewHelper::setFlash('erro', 'Produto não localizado pelo código informado.');
            Router::redirecionar('guarda/executar/' . $pedidoId);
        }

        $endereco = EnderecoModel::buscarPorCodigo($enderecoCodigo);
        if ($endereco === null) {
            ViewHelper::setFlash('erro', 'Endereço físico inválido (padrão RUA-PREDIO-NIVEL).');
            Router::redirecionar('guarda/executar/' . $pedidoId);
        }
        if ((int) $endereco['quarantena'] === 1) {
            ViewHelper::setFlash('erro', 'Não é permitido guardar mercadorias na Quarentena.');
            Router::redirecionar('guarda/executar/' . $pedidoId);
        }

        $r = PedidoModel::guardarItem($pedidoId, (int) $produto['id'], (int) $endereco['id']);

        if ($r['ok'] && $r['concluido']) {
            ViewHelper::setFlash('sucesso', 'Guarda concluída. Carga liberada para a Separação (A Separar).');
            Router::redirecionar('guarda');
        } elseif ($r['ok']) {
            ViewHelper::setFlash('sucesso', $r['mensagem']);
            Router::redirecionar('guarda/executar/' . $pedidoId);
        } else {
            ViewHelper::setFlash('erro', $r['mensagem']);
            Router::redirecionar('guarda/executar/' . $pedidoId);
        }
    }

    /**
     * Sugere o endereço de destino: prefere um local com o mesmo produto,
     * senão o primeiro endereço físico com capacidade disponível.
     */
    private static function sugerirEndereco(int $produtoId): array
    {
        $pdo = Database::conexao();

        // 1) Endereço que já possui saldo do produto (consolidação)
        $sql = 'SELECT e.* FROM estoque_saldos es
                INNER JOIN enderecos e ON e.id = es.endereco_id
                WHERE es.produto_id = :p AND es.quantidade > 0 AND es.status_saldo = "DISPONIVEL"
                  AND e.deleted_at IS NULL AND e.quarantena = 0
                ORDER BY es.updated_at DESC LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':p' => $produtoId]);
        $reg = $stmt->fetch();
        if ($reg) {
            return $reg;
        }

        // 2) Primeiro endereço com espaço (saldo somado < capacidade)
        $sql = 'SELECT e.*, COALESCE(SUM(es.quantidade),0) AS usado
                FROM enderecos e
                LEFT JOIN estoque_saldos es ON es.endereco_id = e.id AND es.status_saldo = "DISPONIVEL"
                WHERE e.deleted_at IS NULL AND e.quarantena = 0
                GROUP BY e.id
                HAVING usado < e.capacidade_maxima
                ORDER BY e.rua, e.predio, e.nivel LIMIT 1';
        $reg = $pdo->query($sql)->fetch();
        return $reg ?: [];
    }
}