<?php
/**
 * app/controllers/ExpedicaoController.php
 * Expedição: gera a nota (Mercadoria em Trânsito), anima a rota do veículo
 * e confirma a entrega ao destinatário (gera avaliação OTIF positiva).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class ExpedicaoController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $aExpedir   = PedidoModel::listarVendas('A_EXPEDIR');
        $emTransito = PedidoModel::listarVendas('EM_TRANSITO');
        $entregues  = PedidoModel::entreguesRecentes(5);

        foreach ($aExpedir as &$p) {
            $p['sla'] = PedidoModel::classificarSla($p);
            $p['itens_count'] = count(PedidoModel::itens((int) $p['id']));
        }
        unset($p);
        foreach ($emTransito as &$p) {
            $p['itens_count'] = count(PedidoModel::itens((int) $p['id']));
        }
        unset($p);

        ViewHelper::render('expedicao/index', [
            'titulo'    => 'Expedição',
            'subtitulo' => 'Gere a nota, coloque o veículo em rota e confirme a entrega ao destinatário.',
            'aExpedir'  => $aExpedir,
            'emTransito' => $emTransito,
            'entregues' => $entregues,
        ]);
    }

    /**
     * "Gerar Nota e Expedir": A_EXPEDIR -> EM_TRANSITO (Mercadoria em Trânsito).
     */
    public function actionExpedir(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $r = PedidoModel::iniciarExpedicao($pedidoId);
        if ($r['ok']) {
            ViewHelper::setFlash('sucesso', 'Nota gerada para ' . $r['nota'] . ' — Mercadoria em Trânsito.');
        } else {
            ViewHelper::setFlash('erro', $r['mensagem']);
        }
        Router::redirecionar('expedicao');
    }

    /**
     * Confirma a entrega ao destinatário: EM_TRANSITO -> ENTREGUE (+ OTIF positivo).
     */
    public function actionConfirmarEntrega(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $r = PedidoModel::confirmarEntrega($pedidoId);
        ViewHelper::setFlash($r['ok'] ? 'sucesso' : 'erro', $r['mensagem']);
        Router::redirecionar('expedicao');
    }
}