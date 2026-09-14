<?php
/**
 * app/controllers/PickingController.php
 * Picking (separação): bipagem item a item e baixa de estoque ao concluir.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class PickingController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $aSeparar = PedidoModel::listarVendas('A_SEPARAR');
        foreach ($aSeparar as &$p) {
            $p['sla'] = PedidoModel::classificarSla($p);
            $p['itens_count'] = count(PedidoModel::itens((int) $p['id']));
        }
        unset($p);

        ViewHelper::render('picking/index', [
            'titulo'    => 'Picking (Separação)',
            'subtitulo' => 'Pedidos de venda aguardando separação. Bipe 1x por produto e conclua para baixar o estoque e enviar ao Packing.',
            'aSeparar'  => $aSeparar,
        ]);
    }

    public function actionConferir(int $pedidoId): void
    {
        AuthHelper::requireLogin();

        $pedido = PedidoModel::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] !== 'A_SEPARAR') {
            ViewHelper::setFlash('erro', 'Pedido não disponível para o Picking.');
            Router::redirecionar('picking');
        }

        $itens = PedidoModel::itens($pedidoId);
        foreach ($itens as &$item) {
            $item['localizacoes'] = EstoqueModel::consultarPorProduto($item['sku']);
        }
        unset($item);

        ViewHelper::render('picking/conferir', [
            'titulo'    => 'Estação de Picking',
            'subtitulo' => 'Pedido ' . $pedido['numero_nota_xml'] . ' · ' . $pedido['cliente_nome'] . ' — bipe 1x por produto coletado (embalagem etiquetada).',
            'pedido'    => $pedido,
            'itens'     => $itens,
            'progresso' => PedidoModel::progressoPicking($pedidoId),
        ]);
    }

    public function actionBipar(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $codigo = trim($_POST['codigo'] ?? '');
        if ($codigo === '') {
            ViewHelper::setFlash('erro', 'Leia ou digite um código de barras.');
            Router::redirecionar('picking/conferir/' . $pedidoId);
        }

        $r = PedidoModel::biparPicking($pedidoId, $codigo);
        ViewHelper::setFlash($r['ok'] ? 'sucesso' : 'aviso', $r['mensagem']);
        Router::redirecionar('picking/conferir/' . $pedidoId);
    }

    public function actionDesfazer(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $codigo = trim($_POST['codigo'] ?? '');
        if ($codigo === '') {
            ViewHelper::setFlash('erro', 'Informe o código do produto para desfazer a separação.');
            Router::redirecionar('picking/conferir/' . $pedidoId);
        }

        $produto = ProdutoModel::buscarPorSku($codigo);
        if ($produto === null) {
            ViewHelper::setFlash('erro', 'Nenhum produto com este SKU. Digite apenas o SKU (ex.: SIS-002).');
            Router::redirecionar('picking/conferir/' . $pedidoId);
        }

        PedidoModel::desfazerPicking($pedidoId, (int) $produto['id']);
        ViewHelper::setFlash('aviso', 'Separação desfeita para este produto.');
        Router::redirecionar('picking/conferir/' . $pedidoId);
    }

    /**
     * Conclui o Picking: baixa o estoque separado e envia o pedido ao Packing.
     */
    public function actionConcluir(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $r = PedidoModel::concluirPicking($pedidoId);
        ViewHelper::setFlash($r['ok'] ? 'sucesso' : 'erro', $r['mensagem']);
        Router::redirecionar($r['ok'] ? 'packing' : 'picking/conferir/' . $pedidoId);
    }
}