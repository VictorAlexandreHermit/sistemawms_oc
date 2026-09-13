<?php
/**
 * app/controllers/SeparacaoController.php
 * Picking/Packing: validação por bipagem item a item e liberação da expedição.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class SeparacaoController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $aSeparar = PedidoModel::listarPorStatus('A_SEPARAR');
        $aExpedir = PedidoModel::listarPorStatus('A_EXPEDIR');
        $entregues = PedidoModel::entreguesRecentes();

        foreach ($aSeparar as &$p) { $p['sla'] = PedidoModel::classificarSla($p); }
        unset($p);
        foreach ($aExpedir as &$p) { $p['sla'] = PedidoModel::classificarSla($p); }
        unset($p);

        ViewHelper::render('separacao/index', [
            'titulo'    => 'Separação &amp; Embalagem',
            'subtitulo' => 'Bipe obrigatório item a item; a expedição só é liberada com 100% da conferência.',
            'aSeparar'  => $aSeparar,
            'aExpedir'  => $aExpedir,
            'entregues' => $entregues,
        ]);
    }

    public function actionConferir(int $pedidoId): void
    {
        AuthHelper::requireLogin();

        $pedido = PedidoModel::buscarPorId($pedidoId);
        if ($pedido === null || !in_array($pedido['status_kanban'], ['A_SEPARAR', 'A_EXPEDIR'], true)) {
            ViewHelper::setFlash('erro', 'Pedido não disponível para conferência.');
            Router::redirecionar('separacao');
        }

        $itens = PedidoModel::itens($pedidoId);
        // Enriquecer cada item com os endereços onde há saldo Disponível
        foreach ($itens as &$item) {
            $item['localizacoes'] = EstoqueModel::consultarPorProduto($item['codigo_barras']);
        }
        unset($item);

        ViewHelper::render('separacao/conferir', [
            'titulo'    => 'Estação de Picking &amp; Packing',
            'subtitulo' => 'Pedido ' . $pedido['numero_nota_xml'] . ' · ' . $pedido['cliente_nome'] . ' — bipe os itens coletados.',
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
            Router::redirecionar('separacao/conferir/' . $pedidoId);
        }

        $r = PedidoModel::biparPicking($pedidoId, $codigo);
        ViewHelper::setFlash($r['ok'] ? 'sucesso' : 'aviso', $r['mensagem']);
        Router::redirecionar('separacao/conferir/' . $pedidoId);
    }

    public function actionConcluir(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $r = PedidoModel::concluirEmbalagem($pedidoId);
        ViewHelper::setFlash($r['ok'] ? 'sucesso' : 'erro', $r['mensagem']);
        Router::redirecionar('separacao');
    }

    public function actionExpedir(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $r = PedidoModel::expedirPedido($pedidoId);
        ViewHelper::setFlash($r['ok'] ? 'sucesso' : 'erro', $r['mensagem']);
        Router::redirecionar('separacao');
    }
}