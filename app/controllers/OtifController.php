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
            ViewHelper::render('otif/invalido', ['titulo' => 'Link de avaliação não encontrado']);
            return;
        }

        if (PesquisaOtifModel::expirada($pesquisa)) {
            ViewHelper::render('otif/invalido', [
                'titulo'   => 'Avaliação expirada',
                'mensagem' => 'O prazo para avaliar esta entrega já foi ultrapassado.',
            ]);
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
}

