<?php
/**
 * app/controllers/OtifController.php
 * Página pública de avaliação do cliente (token) e painel interno OTIF.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class OtifController
{
    // ----------- Público (cliente final, sem login) -----------

    public function actionAvaliar(): void
    {
        $token = trim($_GET['token'] ?? '');

        $pesquisa = $token !== '' ? PesquisaOtifModel::buscarPorToken($token) : null;

        if ($pesquisa === null) {
ViewHelper::render('otif/invalido', [
                'titulo' => 'Link de avaliação não encontrado',
            ], 'externo');
            return;
        }

        if (PesquisaOtifModel::expirada($pesquisa)) {
ViewHelper::render('otif/invalido', [
                'titulo'    => 'Avaliação expirada',
                'mensagem'  => 'O prazo para avaliar esta entrega (10 dias úteis) já foi ultrapassado.',
            ], 'externo');
            return;
        }

        if ($pesquisa['respondido_em'] !== null) {
ViewHelper::render('otif/invalido', [
                'titulo'    => 'Avaliação já enviada',
                'mensagem'  => 'Obrigado! Sua resposta já foi registrada em ' . DateHelper::exibirData($pesquisa['respondido_em']) . '.',
            ], 'externo');
            return;
        }

ViewHelper::render('otif/avaliar', [
            'titulo'    => 'Avalie sua entrega',
            'pesquisa'  => $pesquisa,
        ], 'externo');
    }

    public function actionResponder(): void
    {
        CsrfHelper::checarRequisicao();

        $token   = trim($_POST['token'] ?? '');
        $pesquisa = $token !== '' ? PesquisaOtifModel::buscarPorToken($token) : null;

if ($pesquisa === null) {
            ViewHelper::render('otif/invalido', ['titulo' => 'Link de avaliação não encontrado'], 'externo');
            return;
        }

        if (PesquisaOtifModel::expirada($pesquisa)) {
            ViewHelper::render('otif/invalido', [
                'titulo'   => 'Avaliação expirada',
                'mensagem' => 'O prazo para avaliar esta entrega já foi ultrapassado.',
            ], 'externo');
            return;
        }

        try {
PesquisaOtifModel::registraResposta($pesquisa, $_POST, $_FILES['foto_1'] ?? null, $_FILES['foto_2'] ?? null);
            ViewHelper::render('otif/obrigado', [
                'titulo' => 'Avaliação enviada',
            ], 'externo');
        } catch (\Throwable $e) {
ViewHelper::setFlash('erro', $e->getMessage());
            ViewHelper::render('otif/avaliar', [
                'titulo'   => 'Avalie sua entrega',
                'pesquisa' => $pesquisa,
            ], 'externo');
        }
    }

    // ----------- Área logada -----------

public function actionPainel(): void
    {
        AuthHelper::requireLogin();

        $status = strtoupper(trim($_GET['status'] ?? ''));
        ViewHelper::render('otif/painel', [
            'titulo'    => 'Painel OTIF',
            'subtitulo' => 'On Time & In Full — metas de prazo, avaria e conformidade de itens.',
            'pesquisas' => PesquisaOtifModel::listar(in_array($status, ['PENDENTE', 'ENVIADO', 'FALHA']) ? $status : null),
            'taxas'     => PesquisaOtifModel::taxas(),
            'criticas'  => PesquisaOtifModel::criticas(),
            'filtro'    => $status,
        ]);
    }

    public function actionResposta(int $id): void
    {
        AuthHelper::requireLogin();

        $pesquisa = PesquisaOtifModel::buscarPorId($id);
        if ($pesquisa === null) {
            ViewHelper::setFlash('erro', 'Avaliação não encontrada.');
            Router::redirecionar('otif/painel');
        }

        ViewHelper::render('otif/resposta', [
            'titulo'    => 'Avaliação OTIF',
            'subtitulo' => 'Resposta do cliente para a nota ' . SecurityHelper::e($pesquisa['numero_nota_xml']) . '.',
            'pesquisa'  => $pesquisa,
        ]);
    }

    public function actionReenviar(int $id): void
    {
        AuthHelper::requirePerfil('GESTOR');
        CsrfHelper::checarRequisicao();

        $pesquisa = PesquisaOtifModel::buscarPorId($id);
        if ($pesquisa === null) {
            ViewHelper::setFlash('erro', 'Avaliação não encontrada.');
            Router::redirecionar('otif/painel');
        }

        $pedido = PedidoModel::buscarPorId((int) $pesquisa['pedido_id']);

        $contato = trim($_POST['contato'] ?? '');
        if ($contato !== '') {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare('UPDATE pedidos SET cliente_contato = :contato WHERE id = :id');
            $stmt->execute([':contato' => mb_substr($contato, 0, 100), ':id' => (int) $pesquisa['pedido_id']]);
            $pedido['cliente_contato'] = $contato;
        }

        if ($pedido === null || $pedido['status_kanban'] !== 'ENTREGUE') {
            ViewHelper::setFlash('erro', 'Reenvio só é possível para pedidos já entregues.');
            Router::redirecionar('otif/painel');
        }

        $ok = OtifApiHelper::disparar($pedido);
        ViewHelper::setFlash($ok ? 'sucesso' : 'erro',
            $ok ? 'Avaliação reenviada para ' . SecurityHelper::e($pedido['cliente_contato'] ?: 'o contato do cliente') . '.' : 'Falha no reenvio (verifique o modo/envio e o contato).');
        Router::redirecionar('otif/painel');
    }

    public function actionLimparHistorico(): void
    {
        AuthHelper::requirePerfil('GESTOR');
        CsrfHelper::checarRequisicao();

        try {
            PesquisaOtifModel::limparHistorico();
            ViewHelper::setFlash('sucesso', 'Histórico OTIF e de entregas limpo com sucesso.');
        } catch (RuntimeException $e) {
            ViewHelper::setFlash('erro', $e->getMessage());
        }
        Router::redirecionar('otif/painel');
    }
}

