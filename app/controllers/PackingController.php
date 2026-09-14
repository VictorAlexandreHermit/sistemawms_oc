<?php
/**
 * app/controllers/PackingController.php
 * Packing (embalagem): itens separados pelo Picking são embalados e
 * verificados para liberação da Expedição.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class PackingController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $aEmbalar = PedidoModel::listarVendas('A_EMBALAR');
        foreach ($aEmbalar as &$p) {
            $p['sla'] = PedidoModel::classificarSla($p);
            $p['itens_count'] = count(PedidoModel::itens((int) $p['id']));
        }
        unset($p);

        ViewHelper::render('packing/index', [
            'titulo'    => 'Packing (Embalagem)',
            'subtitulo' => 'Pedidos separados pelo Picking aguardando embalagem e verificação.',
            'aEmbalar'  => $aEmbalar,
        ]);
    }

    public function actionConferir(int $pedidoId): void
    {
        AuthHelper::requireLogin();

        $pedido = PedidoModel::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] !== 'A_EMBALAR') {
            ViewHelper::setFlash('erro', 'Pedido não disponível para o Packing.');
            Router::redirecionar('packing');
        }

        ViewHelper::render('packing/conferir', [
            'titulo'    => 'Estação de Packing',
            'subtitulo' => 'Pedido ' . $pedido['numero_nota_xml'] . ' · ' . $pedido['cliente_nome'] . ' — embale, verifique os itens e libere para a Expedição.',
            'pedido'    => $pedido,
            'itens'     => PedidoModel::itens($pedidoId),
            'progresso' => PedidoModel::progressoPicking($pedidoId),
        ]);
    }

    public function actionConcluir(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $r = PedidoModel::concluirEmbalagem($pedidoId);
        ViewHelper::setFlash($r['ok'] ? 'sucesso' : 'erro', $r['mensagem']);
        Router::redirecionar($r['ok'] ? 'expedicao' : 'packing/conferir/' . $pedidoId);
    }
}